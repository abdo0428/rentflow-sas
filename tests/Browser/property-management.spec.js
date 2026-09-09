import { test, expect } from '@playwright/test';

for (const scenario of [{ locale: 'en', width: 1440, height: 1000 }, { locale: 'ar', width: 390, height: 844 }]) {
    test(`property CRUD and safe deletion in ${scenario.locale} at ${scenario.width}px`, async ({ page }, testInfo) => {
        test.setTimeout(120000);
        const ar = scenario.locale === 'ar';
        const labels = {
            saveBuilding: ar ? 'حفظ المبنى' : 'Save building',
            addUnit: ar ? 'إضافة وحدة' : 'Add unit',
            saveUnit: ar ? 'حفظ الوحدة' : 'Save unit',
            saveTenant: ar ? 'حفظ المستأجر' : 'Save tenant',
            edit: ar ? 'تعديل' : 'Edit',
            remove: ar ? 'حذف' : 'Delete',
            confirm: ar ? 'نعم، احذف السجل' : 'Yes, delete record',
        };
        await page.setViewportSize({ width: scenario.width, height: scenario.height });
        const jsErrors = [];
        page.on('pageerror', error => jsErrors.push(error.message));
        await page.goto('/login');
        await page.getByRole('button', { name: ar ? 'العربية' : 'English', exact: true }).click();
        await page.locator('[name="email"]').fill('admin@example.com');
        await page.locator('[name="password"]').fill('password');
        await page.getByRole('button', { name: ar ? 'تسجيل الدخول' : 'Sign in', exact: true }).click();
        await expect(page).toHaveURL(/\/dashboard$/);
        const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
        const suffix = String(Date.now()).slice(-9);
        let buildingUrl, unitUrl, tenantUrl;
        const fill = async (fields) => {
            for (const [name, value] of Object.entries(fields)) {
                await page.locator(`[name="${name}"]`).fill(value);
            }
        };
        const screenshot = async name => {
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
            await page.screenshot({ path: testInfo.outputPath(name + '.png'), fullPage: true });
        };
        const remove = async url => {
            await page.goto(url);
            await page.getByRole('button', { name: labels.remove, exact: true }).click();
            await expect(page.getByRole('dialog')).toBeVisible();
            await page.getByRole('button', { name: labels.confirm, exact: true }).click();
        };
        try {
            await page.goto('/buildings/create');
            await fill({ name: 'River House ' + suffix, code: 'E2E-' + suffix, address: '12 River Street', city: 'Riyadh', district: 'Olaya', total_floors: '4', notes: 'Browser acceptance test' });
            await screenshot('building-form');
            await page.getByRole('button', { name: labels.saveBuilding, exact: true }).click();
            await expect(page).toHaveURL(/\/buildings\/\d+$/);
            buildingUrl = new URL(page.url()).pathname;
            await expect(page.getByRole('status')).toContainText(ar ? 'تم إنشاء المبنى' : 'Building created');
            await page.getByRole('link', { name: labels.addUnit, exact: true }).click();
            await expect(page.locator('[name="building_id"]')).toHaveValue(buildingUrl.split('/').at(-1));
            await fill({ unit_number: 'T-101', floor: '2', bedrooms: '2', bathrooms: '2', area: '102.50', rent_amount: '3750.50' });
            await screenshot('unit-form');
            await page.getByRole('button', { name: labels.saveUnit, exact: true }).click();
            await expect(page).toHaveURL(/\/units\/\d+$/);
            unitUrl = new URL(page.url()).pathname;
            await expect(page.getByRole('heading', { level: 1 })).toContainText('T-101');
            await screenshot('unit-details');
            await remove(buildingUrl);
            await expect(page.getByRole('alert')).toContainText(ar ? 'هذا المبنى يحتوي على وحدات' : 'This building contains units');
            await expect(page.locator('tbody tr')).toHaveCount(1);
            await screenshot('safe-delete-feedback');
            await page.getByRole('link', { name: labels.edit, exact: true }).click();
            await page.locator('[name="name"]').fill('River House Updated ' + suffix);
            await page.getByRole('button', { name: labels.saveBuilding, exact: true }).click();
            await expect(page.getByRole('heading', { level: 1 })).toContainText('Updated');
            await page.goto('/buildings?q=' + suffix);
            await expect(page.locator('tbody tr')).toHaveCount(1);
            await screenshot('buildings-filtered');
            await page.goto('/tenants/create');
            await fill({ full_name: 'Lina ' + suffix, email: 'lina-' + suffix + '@example.com', phone: '+966555000000', national_id: 'TEST-' + suffix, emergency_contact_name: 'Ali Hassan', emergency_contact_phone: '+966555111111' });
            await page.getByRole('button', { name: labels.saveTenant, exact: true }).click();
            await expect(page).toHaveURL(/\/tenants\/\d+$/);
            tenantUrl = new URL(page.url()).pathname;
            await expect(page.getByText('Ali Hassan', { exact: true })).toBeVisible();
            await screenshot('tenant-details');
            await page.getByRole('button', { name: ar ? 'فتح قائمة المستخدم' : 'Open user menu', exact: true }).click();
            await expect(page.getByRole('link', { name: ar ? 'الحساب والأمان' : 'Account & security' })).toBeVisible();
            await page.keyboard.press('Escape');
            await expect(page.getByRole('link', { name: ar ? 'الحساب والأمان' : 'Account & security' })).toBeHidden();
            await remove(tenantUrl);
            await expect(page).toHaveURL(/\/tenants$/);
            tenantUrl = null;
            await remove(unitUrl);
            await expect(page).toHaveURL(/\/units$/);
            unitUrl = null;
            await remove(buildingUrl);
            await expect(page).toHaveURL(/\/buildings$/);
            buildingUrl = null;
            expect(jsErrors).toEqual([]);
        } finally {
            for (const url of [tenantUrl, unitUrl, buildingUrl].filter(Boolean)) {
                await page.request.delete(url, { headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' } });
            }
        }
    });
}
