export const brand = {
  name: process.env.NEXT_PUBLIC_BRAND_NAME ?? "Tunduk Trips",
  siteUrl: process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000",
  /** Digits only, international format, e.g. 996555123456. Empty until the WhatsApp Business number exists (step 8.1). */
  whatsapp: process.env.NEXT_PUBLIC_WHATSAPP_NUMBER ?? "",
  email: process.env.NEXT_PUBLIC_CONTACT_EMAIL ?? "",
  /** Shows the "sample data" notice while the catalog runs on demo content. */
  demoContent: process.env.NEXT_PUBLIC_DEMO_CONTENT !== "false",
};

/** wa.me link with a prefilled message (launch document, item 26). Without a number WhatsApp asks whom to send to. */
export function whatsappUrl(text: string): string {
  return `https://wa.me/${brand.whatsapp}?text=${encodeURIComponent(text)}`;
}
