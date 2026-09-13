import { expect, Locator, Page } from "@playwright/test";
import BasePage from "@hipanel-core/page/BasePage";

export default class Form extends BasePage {
  protected formLocator: Locator;

  constructor(readonly page: Page) {
    super(page);
    this.formLocator = page.locator(".content form").first();
  }

  async submit(text?: string | null): Promise<void> {
    await this.page.waitForLoadState("networkidle");
    let submitButton: Locator;
    if (text) {
      submitButton = this.formLocator.locator("button[type=submit]").filter({ hasText: text }).first();
    } else {
      submitButton = this.formLocator.locator("button[type=submit]").first();
    }

    await submitButton.waitFor({ state: "visible" });
    await expect(submitButton).toBeEnabled();

    await submitButton.click();
    // "networkidle" can hang indefinitely when the page has persistent background
    // traffic (analytics beacons, the Yii debug toolbar, etc.) that never truly
    // goes quiet. Callers already assert on a concrete post-submit signal (the
    // success/error alert, a specific heading, ...), so waiting only for the
    // navigated document to be parsed is enough and far more reliable.
    await this.page.waitForLoadState("domcontentloaded");
  }

  async seeAlert(text: string): Promise<void> {
    await this.page.waitForSelector("div[role=alert]");
    await expect(this.page.locator("div[role=alert]")).toContainText(text);
  }

  async cancel(): Promise<void> {
    const cancelButton = this.formLocator.getByRole("button", { name: "Cancel" }).first();
    await cancelButton.click();
  }

  async hasErrors(): Promise<boolean> {
    return await this.formLocator.locator(":scope .help-block-error").count() > 0;
  }
}
