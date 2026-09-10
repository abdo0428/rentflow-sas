import { test, expect } from '@playwright/test';
import { readFileSync } from 'node:fs';

for (const viewport of [{ name: 'desktop', width: 1440, height: 1000 }, { name: 'mobile', width: 390, height: 844 }]) {
    for (const locale of ['en', 'ar']) {
        test(`${viewport.name} ${locale}: tenant portal, private PDFs and safe offline mode`, async ({ page, context }, testInfo) => {
            const ar = locale === 'ar';
            await page.setViewportSize(viewport);
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.goto('/login');
            await page.getByRole('button', { name: ar ? 'العربية' : 'English', exact: true }).click();
            await page.locator('[name="email"]').fill('tenant@example.com');
            await page.locator('[name="password"]').fill('password');
            await page.locator('form[action$="/login"] button[type="submit"]').click();
            await expect(page).toHaveURL(/\/dashboard$/);
            await expect.poll(() => page.evaluate(() => Boolean(navigator.serviceWorker.controller)), { timeout: 15000 }).toBe(true);
            const checkPage = async name => {
                await expect(page.locator('html')).toHaveAttribute('dir', ar ? 'rtl' : 'ltr');
                expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
                expect(await page.locator('main').innerText()).not.toMatch(/\b(?:portal|workflow|property|app)\.[a-z_]+/);
                await page.screenshot({ path: testInfo.outputPath(name + '.png'), fullPage: true, animations: 'disabled' });
            };
            await checkPage('dashboard');
            if (viewport.name === 'mobile') {
                await page.getByRole('button', { name: ar ? 'المزيد' : 'More', exact: true }).click();
                await expect(page.getByRole('dialog')).toBeVisible();
                await expect(page.getByRole('dialog').getByRole('link')).toHaveCount(8);
                await page.keyboard.press('Escape');
                await expect(page.getByRole('dialog')).toBeHidden();
            }
            for (const [path, name] of [['/tenant/unit','unit'], ['/tenant/lease','lease'], ['/payments','payments'], ['/maintenance','maintenance'], ['/maintenance/create','maintenance-create'], ['/tenant/announcements','announcements'], ['/tenant/documents','documents'], ['/profile','profile']]) {
                const response = await page.goto(path);
                expect(response.status()).toBe(200);
                expect(response.headers()['cache-control']).toContain('no-store');
                await checkPage(name);
            }
            await page.goto('/tenant/lease');
            const contractUrl = await page.locator('a[href$="/pdf"]').getAttribute('href');
            const contractDownload = page.waitForEvent('download');
            await page.locator('a[href$="/pdf"]').click();
            const contract = await contractDownload;
            expect(await contract.failure()).toBeNull();
            await contract.saveAs(testInfo.outputPath('contract-' + locale + '.pdf'));
            await page.goto('/payments?status=paid');
            const receiptDownload = page.waitForEvent('download');
            await page.locator('a[href$="/receipt"]').first().click();
            const receipt = await receiptDownload;
            expect(await receipt.failure()).toBeNull();
            await receipt.saveAs(testInfo.outputPath('receipt-' + locale + '.pdf'));
            await page.goto('/tenant/documents');
            const shared = page.locator('a[href$="/download"]').first();
            const documentDownload = page.waitForEvent('download');
            await shared.click();
            expect(await (await documentDownload).failure()).toBeNull();
            await page.goto('/maintenance');
            await page.locator('main a[href*="/maintenance/"]').filter({ has: page.locator('h2') }).first().click();
            await checkPage('maintenance-timeline');
            await expect(page.locator('[name="assigned_to"],[name="status"],[name="body"]')).toHaveCount(0);
            const cachedPaths = await page.evaluate(async () => {
                const paths = [];
                for (const key of await caches.keys()) {
                    for (const request of await (await caches.open(key)).keys()) paths.push(new URL(request.url).pathname);
                }
                return paths;
            });
            expect(cachedPaths).toContain('/offline.html');
            for (const path of cachedPaths) expect(path).toMatch(/^\/(build\/assets\/[^/]+\.(css|js|woff2?|png|svg)|icons\/icon-(192|512)\.png|offline\.html|manifest\.json)$/);
            await context.setOffline(true);
            await page.goto('/payments');
            await expect(page.getByRole('heading', { name: ar ? 'أنت غير متصل بالإنترنت' : "You're offline", exact: true })).toBeVisible();
            await expect(page.locator('body')).not.toContainText('Omar Al Harbi');
            await expect(page.locator('body')).not.toContainText('3,250');
            await checkPage('offline');
            await page.goto(contractUrl);
            await expect(page.locator('html')).toHaveAttribute('lang', locale);
            await expect(page.locator('body')).not.toContainText('RF-DEMO-001');
            await context.setOffline(false);
            await page.goto('/dashboard');
            await expect(page.locator('main h1')).toContainText(ar ? 'مرحبًا' : 'Welcome home');
            expect(errors).toEqual([]);
        });
    }
}

test('mobile Arabic maintenance employee and company dashboards remain usable', async ({ page }, testInfo) => {
    await page.setViewportSize({ width: 390, height: 844 });
    for (const role of ['maintenance', 'admin', 'super']) {
        await page.goto('/login');
        await page.getByRole('button', { name: 'العربية', exact: true }).click();
        await page.locator('[name="email"]').fill(role + '@example.com');
        await page.locator('[name="password"]').fill('password');
        await page.locator('form[action$="/login"] button[type="submit"]').click();
        await expect(page).toHaveURL(/\/dashboard$/);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
        await page.screenshot({ path: testInfo.outputPath(role + '-dashboard.png'), fullPage: true, animations: 'disabled' });
        if (role === 'maintenance') {
            await page.goto('/maintenance');
            await expect(page.locator('tbody tr')).toHaveCount(1);
            await page.locator('tbody tr a').first().click();
            await expect(page.locator('[name="body"]')).toBeVisible();
            await page.screenshot({ path: testInfo.outputPath('staff-request.png'), fullPage: true, animations: 'disabled' });
        }
        await page.locator('aside form[action$="/logout"]').evaluate(form => form.requestSubmit());
        await expect(page).toHaveURL(/\/login$/);
    }
});

test('real PHP upload boundary accepts a 3 MB image and rejects oversized uploads with Arabic feedback', async ({ page }) => {
    await page.goto('/login');
    await page.getByRole('button', { name: 'العربية', exact: true }).click();
    await page.locator('[name="email"]').fill('tenant@example.com');
    await page.locator('[name="password"]').fill('password');
    await page.locator('form[action$="/login"] button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard$/);
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    for (const megabytes of [3, 6]) {
        const response = await page.request.post('/maintenance', {
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
            multipart: {
                unit_id: '0', title: 'حد الرفع', description: 'اختبار التحقق دون إنشاء طلب',
                priority: 'invalid',
                photo: { name: 'photo.png', mimeType: 'image/png', buffer: Buffer.concat([readFileSync('public/icons/icon-192.png'), Buffer.alloc(megabytes * 1024 * 1024)]) },
            },
        });
        expect(response.status()).toBe(422);
        const { errors } = await response.json();
        expect(errors.priority).toBeDefined();
        if (megabytes === 3) expect(errors.photo).toBeUndefined();
        else expect(errors.photo.join(' ')).toContain('تعذّر رفع');
    }
});
