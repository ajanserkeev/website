import { brand } from "@/lib/brand";

export default function Home() {
  return (
    <main className="flex flex-1 flex-col items-center justify-center gap-4 px-4 py-24 text-center">
      <h1 className="text-4xl font-semibold tracking-tight">{brand.name}</h1>
      <p className="max-w-md text-muted-foreground">
        Tours in Kyrgyzstan with local experts. Horse treks, yurts and mountain lakes. Coming spring
        2027.
      </p>
    </main>
  );
}
