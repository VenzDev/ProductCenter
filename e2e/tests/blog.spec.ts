import { test, expect } from "@playwright/test";

test("browses the blog from the list to a single post", async ({ page }) => {
  await page.goto("/en/blog");

  await expect(page.getByRole("heading", { name: "Blog", level: 1 })).toBeVisible();
  await page.getByRole("link", { name: /Hello World/ }).click();

  await expect(page).toHaveURL(/\/en\/blog\/hello-world$/);
  await expect(page.getByRole("heading", { name: "Hello World", level: 1 })).toBeVisible();

  // Scoped to the header: the breadcrumb on this page also has a "Blog" link.
  await page.getByRole("banner").getByRole("link", { name: "Blog" }).click();
  await expect(page).toHaveURL(/\/en\/blog$/);
});
