import type { Metadata, Viewport } from "next";
import { JetBrains_Mono, Unbounded, Work_Sans } from "next/font/google";
import { NuqsAdapter } from "nuqs/adapters/next/app";
import { DemoNotice } from "@/components/layout/demo-notice";
import { SiteFooter } from "@/components/layout/site-footer";
import { SiteHeader } from "@/components/layout/site-header";
import { UtmCapture } from "@/components/utm-capture";
import { RatesProvider } from "@/components/rates-provider";
import { brand } from "@/lib/brand";
import { getCurrencyRates } from "@/lib/catalog";
import "./globals.css";

const unbounded = Unbounded({
  variable: "--font-unbounded",
  subsets: ["latin"],
  weight: ["600", "700"],
});

const workSans = Work_Sans({
  variable: "--font-work-sans",
  subsets: ["latin", "latin-ext"],
});

const jetbrainsMono = JetBrains_Mono({
  variable: "--font-jetbrains-mono",
  subsets: ["latin"],
  weight: ["500"],
});

export const metadata: Metadata = {
  metadataBase: new URL(brand.siteUrl),
  title: {
    default: `${brand.name}: Tours in Kyrgyzstan with Local Experts`,
    template: `%s | ${brand.name}`,
  },
  description:
    "Horse treks, yurt stays and mountain lakes from verified local operators. Pay a small deposit to book, the rest on arrival.",
};

export const viewport: Viewport = {
  themeColor: "#ffffff",
};

export default async function RootLayout({ children }: LayoutProps<"/">) {
  const rates = await getCurrencyRates();
  return (
    <html lang="en" className={`${unbounded.variable} ${workSans.variable} ${jetbrainsMono.variable}`}>
      <body className="flex min-h-dvh flex-col">
        <NuqsAdapter>
          <RatesProvider rates={rates}>
            <UtmCapture />
            <DemoNotice />
            <SiteHeader />
            <main className="flex-1">{children}</main>
            <SiteFooter />
          </RatesProvider>
        </NuqsAdapter>
      </body>
    </html>
  );
}
