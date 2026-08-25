import { test, expect } from "@playwright/test";

test("homepage loads and redirects to the default locale", async ({ page }) => {
  await page.goto("/");

  await expect(page).toHaveURL(/\/en$/);
  await expect(page.getByRole("link", { name: "Product Center" })).toBeVisible();
});
