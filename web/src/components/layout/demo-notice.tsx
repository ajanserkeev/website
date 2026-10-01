import { brand } from "@/lib/brand";

export function DemoNotice() {
  if (!brand.demoContent) return null;
  return (
    <div className="bg-sun-50 px-4 py-1.5 text-center text-xs font-medium text-ink">
      Preview: tours, operators, prices and reviews on this site are sample data.
    </div>
  );
}
