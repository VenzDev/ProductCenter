import { test, expect } from "@playwright/test";

test("registers a new account, logs out, then logs back in", async ({ page }) => {
  const name = "Test User";
  const email = `e2e-${Date.now()}@example.com`;
  const password = "password123";

  await page.goto("/en/login");
  await page.getByRole("button", { name: "Create account" }).click();

  await page.getByLabel("Name").fill(name);
  await page.getByLabel("Email").fill(email);
  await page.getByLabel("Password").fill(password);
  await page.getByRole("button", { name: "Create account" }).click();

  await expect(page).toHaveURL(/\/en$/);
  const userMenu = page.getByRole("button", { name: "TU" });
  await expect(userMenu).toBeVisible();

  await userMenu.click();
  await expect(page.getByText(email)).toBeVisible();
  await page.getByRole("menuitem", { name: "Logout" }).click();

  await expect(page.getByRole("button", { name: "Login" })).toBeVisible();

  await page.getByRole("button", { name: "Login" }).click();
  await page.getByLabel("Email").fill(email);
  await page.getByLabel("Password").fill(password);
  await page.getByRole("button", { name: "Log in" }).click();

  await expect(page).toHaveURL(/\/en$/);
  await expect(page.getByRole("button", { name: "TU" })).toBeVisible();
});
