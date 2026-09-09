import { test, expect } from '@playwright/test';

for (const viewport of [{ name: 'desktop', width: 1440, height: 1000 }, { name: 'tablet', width: 820, height: 1180 }, { name: 'mobile', width: 390, height: 844 }]) {
    for (const locale of ['en', 'ar']) {
        test(`${viewport.name} ${locale}: financial screens, lease draft and maintenance workspace`, async ({ page }, testInfo) => {
            const ar = locale === 'ar';
            await page.setViewportSize(viewport);
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.goto('/login');
            await page.getByRole('button', { name: ar ? 'العربية' : 'English', exact: true }).click();
            await page.locator('[name="email"]').fill('admin@example.com');
            await page.locator('[name="password"]').fill('password');
            await page.locator('form[action$="/login"] button[type="submit"]').click();
            await expect(page).toHaveURL(/\/dashboard$/);
            const checkPage = async (name) => {
                await expect(page.locator('html')).toHaveAttribute('dir', ar ? 'rtl' : 'ltr');
                expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
                expect(await page.locator('main').innerText()).not.toMatch(/\b(?:workflow|property|app)\.[a-z_]+/);
                await page.screenshot({ path: testInfo.outputPath(name + '.png'), fullPage: true });
            };
            await page.goto('/payments');
            await expect(page.locator('tbody tr').first()).toBeVisible();
            await checkPage('payments');
            await page.locator('tbody tr').first().getByRole('link').first().click();
            await checkPage('payment-details');
            await page.goto('/payments?status=pending');
            await page.locator('tbody tr').first().getByRole('link').first().click();
            await page.locator('[name="payment_method"]').selectOption('cash');
            await page.getByRole('button', { name: ar ? 'تسجيل الدفع' : 'Mark as paid', exact: true }).click();
            await expect(page.getByRole('dialog')).toBeVisible();
            await checkPage('payment-confirmation');
            await page.getByRole('dialog').getByRole('button', { name: ar ? 'إلغاء' : 'Cancel', exact: true }).click();
            await expect(page.getByRole('dialog')).toBeHidden();
            await page.goto('/payments/overdue');
            await checkPage('overdue');
            await page.goto('/maintenance');
            await checkPage('maintenance-list');
            await page.locator('tbody tr').first().getByRole('link').first().click();
            await checkPage('maintenance-details');
            await page.goto('/maintenance/create');
            await expect(page.locator('[name="unit_id"]')).toBeVisible();
            await checkPage('maintenance-create');
            await page.goto('/notifications');
            await checkPage('notifications');

            let draftUrl;
            try {
                await page.goto('/leases/create');
                const number = 'BROWSER-' + Date.now();
                await page.locator('[name="contract_number"]').fill(number);
                await page.locator('[name="tenant_id"]').selectOption({ index: 1 });
                await page.locator('[name="unit_id"]').selectOption({ index: 1 });
                await page.locator('[name="monthly_rent"]').fill('1800');
                await page.locator('[name="payment_due_day"]').fill('31');
                await checkPage('lease-form');
                await page.locator('form[action$="/leases"] button[type="submit"]').click();
                await expect(page).toHaveURL(/\/leases\/\d+$/);
                draftUrl = new URL(page.url()).pathname;
                await expect(page.getByRole('heading', { level: 1 })).toHaveText(number);
                await checkPage('lease-details');
                await page.goto(draftUrl + '/edit');
                await page.locator('[name="contract_number"]').fill(number + '-EDIT');
                await page.locator('button[type="submit"]').filter({ hasText: ar ? 'حفظ' : 'Save' }).click();
                await expect(page).toHaveURL(new RegExp(draftUrl + '$'));
                await expect(page.getByRole('heading', { level: 1 })).toHaveText(number + '-EDIT');
                await page.getByRole('button', { name: ar ? 'حذف' : 'Delete', exact: true }).click();
                await expect(page.getByRole('dialog')).toBeVisible();
                await page.getByRole('dialog').getByRole('button', { name: ar ? 'نعم، احذف السجل' : 'Yes, delete record', exact: true }).click();
                await expect(page).toHaveURL(/\/leases$/);
                draftUrl = null;
            } finally {
                if (draftUrl) {
                    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
                    await page.request.delete(draftUrl, { headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' } });
                }
            }

            await page.locator('aside form[action$="/logout"]').evaluate(form => form.requestSubmit());
            await expect(page).toHaveURL(/\/login$/);
            await page.goto('/login');
            await page.getByRole('button', { name: ar ? 'العربية' : 'English', exact: true }).click();
            await page.locator('[name="email"]').fill('maintenance@example.com');
            await page.locator('[name="password"]').fill('password');
            await page.locator('form[action$="/login"] button[type="submit"]').click();
            await expect(page).toHaveURL(/\/dashboard$/);
            await page.goto('/maintenance');
            await expect(page.locator('tbody tr')).toHaveCount(1);
            await page.locator('tbody tr').first().getByRole('link').first().click();
            await checkPage('staff-request');
            await expect(page.locator('[name="body"]')).toBeVisible();
            await expect(page.locator('[name="assigned_to"]')).toHaveCount(0);
            expect(errors).toEqual([]);
        });
    }
}
