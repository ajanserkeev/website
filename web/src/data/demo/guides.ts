// DEMO CONTENT adapted from the Google Stitch mockup. Fact-check every number with operators
// before publishing (launch document, section 09: "never invent data").
import type { Guide } from "@/lib/types";

export const guides: Guide[] = [
  {
    slug: "song-kul-lake-guide",
    title: "Song-Kul Lake: Yurt Camps, Horse Treks and How to Get There",
    excerpt:
      "Song-Kul is a high mountain lake at 3,016 m where herder families live in yurts from June to September. Here is when to go, how to get there and what to pack.",
    hero: {
      src: "/demo/song-kul-panorama.webp",
      alt: "Yurt camp and grazing horses on the shore of Song-Kul at sunset",
    },
    readingMinutes: 7,
    updatedOn: "2026-10-01",
    facts: [
      { label: "Altitude", value: "3,016 m" },
      { label: "Season", value: "June – September" },
      { label: "Phone signal", value: "Little or none" },
      { label: "Nights", value: "Often below 5 °C" },
    ],
    sections: [
      {
        id: "why-go",
        title: "What makes Song-Kul special",
        paragraphs: [
          "Song-Kul lies in a wide basin of summer pastures (jailoo) surrounded by mountain ridges. There are no towns or hotels on the shore, only yurt camps that families set up for the summer.",
          "From autumn to late spring the lake is frozen and the passes are closed by snow. In June the herders drive their horses, sheep and cattle up, and the pastures fill with life until September.",
        ],
      },
      {
        id: "when-to-go",
        title: "Best time to visit",
        paragraphs: [
          "The season is short. Camps open in mid-June and close in mid-September; dates move with the weather each year.",
        ],
        bullets: [
          "June: camps are being set up, flowers on the pastures, cold nights and possible snow on the passes.",
          "July to mid-August: the warmest days and the busiest camps. The best time for horse treks.",
          "Late August to September: fewer travellers, yellow grass, families start moving down.",
        ],
      },
      {
        id: "getting-there",
        title: "How to get there",
        paragraphs: [
          "Most trips start in Kochkor, about three hours by road from Bishkek. From Kochkor or Naryn, a 4×4 takes you over one of the passes to the lake, or you ride there in two to three days on horseback.",
          "The road from Naryn climbs the Moldo-Ashuu pass with a long series of hairpin bends. The route from Kochkor goes over the Kalmak-Ashuu pass. Both need a high-clearance vehicle.",
        ],
      },
      {
        id: "yurt-camps",
        title: "Where to stay: yurt camps",
        paragraphs: [
          "You sleep in a felt yurt on thick mattresses with heavy blankets. A small stove heats the yurt in the evening. Toilets are simple outhouses; some camps have a solar panel for lights and charging.",
          "Family camps are the simplest and the most personal. A few camps offer beds and more comfort for a higher price.",
        ],
      },
      {
        id: "packing",
        title: "What to pack",
        paragraphs: ["Weather changes fast at 3,000 m. Even in July you can get rain, hail and freezing nights."],
        bullets: [
          "Warm layers, a down jacket, hat and gloves",
          "Waterproof jacket and sturdy shoes",
          "Sunscreen and sunglasses",
          "Power bank and headlamp",
          "Cash in Kyrgyz som: there are no ATMs or card terminals at the lake",
        ],
      },
      {
        id: "etiquette",
        title: "Being a good guest",
        paragraphs: ["Herder families welcome guests warmly. A few customs go a long way:"],
        bullets: [
          "Don't step on the threshold of the yurt.",
          "Accept bread and tea with your right hand or both hands.",
          "Take all your rubbish back with you: there is no waste collection on the pastures.",
        ],
      },
    ],
    tourSlugs: ["song-kul-horse-trek-yurt-stay", "kyrgyzstan-highlights-lakes-tash-rabat"],
  },
];
