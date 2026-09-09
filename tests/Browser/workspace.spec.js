import { test, expect } from '@playwright/test';

for (const viewport of [{ name: 'desktop', width: 1440, height: 1050 }, { name: 'tablet', width: 820, height: 1180 }, { name: 'mobile', width: 390, height: 844 }]) {
    for (const locale of ['en', 'ar']) {
        test(`${viewport.name} ${locale}: login, navigation, language and profile modal`, async ({ page }, testInfo) => {
            await page.setViewportSize({ width: viewport.width, height: viewport.height });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.goto('/login');
            await page.getByRole('button', { name: locale === 'ar' ? 'العربية' : 'English', exact: true }).click();
            await expect(page.locator('html')).toHaveAttribute('dir', locale === 'ar' ? 'rtl' : 'ltr');
            await page.screenshot({ path: testInfo.outputPath('login.png'), fullPage: true });
            await page.locator('[name="email"]').fill('admin@example.com');
            await page.locator('[name="password"]').fill('password');
            await page.getByRole('button', { name: locale === 'ar' ? 'تسجيل الدخول' : 'Sign in', exact: true }).click();
            await expect(page).toHaveURL(/\/dashboard$/);
            await expect(page.getByRole('heading', { level: 1 })).toContainText(locale === 'ar' ? 'مرحبًا' : 'Welcome back');
            await page.screenshot({ path: testInfo.outputPath('dashboard.png'), fullPage: true });
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
            if (viewport.width < 1024) {
                await page.getByRole('button', { name: locale === 'ar' ? 'فتح القائمة' : 'Open navigation' }).click();
                await expect(page.locator('aside')).toBeVisible();
            }
            await page.locator('aside').getByRole('link', { name: locale === 'ar' ? 'الوحدات' : 'Units', exact: true }).click();
            await expect(page).toHaveURL(/\/units$/);
            await expect(page.locator('tbody tr')).toHaveCount(10);
            await page.screenshot({ path: testInfo.outputPath('units.png'), fullPage: true });
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1)).toBe(true);
            await page.getByRole('link', { name: locale === 'ar' ? 'التالي' : 'Next', exact: true }).click();
            await expect(page.locator('tbody tr')).toHaveCount(2);
            await page.goto('/profile');
            await page.getByRole('button', { name: locale === 'ar' ? 'حذف الحساب' : 'Delete account', exact: true }).click();
            await expect(page.getByRole('dialog')).toBeVisible();
            await page.getByRole('button', { name: locale === 'ar' ? 'إلغاء' : 'Cancel', exact: true }).click();
            await expect(page.getByRole('dialog')).toBeHidden();
            expect(errors).toEqual([]);
        });
    }
}
