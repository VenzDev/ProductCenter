import { test, expect } from "@playwright/test";

test("switches the site language between English and Polish", async ({ page }) => {
  await page.goto("/en");
  await expect(page.getByRole("link", { name: "Cart" })).toBeVisible();

  await page.getByRole("button", { name: "English" }).click();
  await page.getByRole("menuitem", { name: "Polski" }).click();

  await expect(page).toHaveURL(/\/pl$/);
  await expect(page.getByRole("link", { name: "Koszyk" })).toBeVisible();

  await page.getByRole("button", { name: "Polski" }).click();
  await page.getByRole("menuitem", { name: "English" }).click();

  await expect(page).toHaveURL(/\/en$/);
  await expect(page.getByRole("link", { name: "Cart" })).toBeVisible();
});
