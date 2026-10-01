<?php

namespace Database\Seeders;

use App\Enums\DepartureStatus;
use App\Enums\ReviewSource;
use App\Enums\TourItemKind;
use App\Enums\TourStatus;
use App\Enums\TourType;
use App\Models\Activity;
use App\Models\Collection;
use App\Models\CurrencyRate;
use App\Models\Operator;
use App\Models\Post;
use App\Models\Region;
use App\Models\Tour;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Demo catalog shared with the web app (web/src/data/demo, exported to data/demo-catalog.json).
 * Operators, reviews, ratings and prices are invented, like the sketches in the launch document.
 */
class DemoCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/data/demo-catalog.json'), true, flags: JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($data) {
            $regions = $this->regions($data['regions']);
            $activities = $this->activities($data['activities']);
            $operators = $this->operators($data['operators']);
            $tours = $this->tours($data['tours'], $operators, $regions, $activities);
            $this->collections($data['collections'], $tours);
            $this->posts($data['guides'], $tours);
            $this->currencyRates();
        });
    }

    /** @return array<string, Region> */
    private function regions(array $rows): array
    {
        $regions = [];
        foreach ($rows as $i => $row) {
            $regions[$row['slug']] = Region::create([
                'name' => $row['name'],
                'slug' => $row['slug'],
                'summary' => $row['summary'],
                'places' => $row['places'],
                'sort' => $i,
            ]);
        }

        return $regions;
    }

    /** @return array<string, Activity> */
    private function activities(array $rows): array
    {
        $activities = [];
        foreach ($rows as $i => $row) {
            $activities[$row['slug']] = Activity::create([
                'name' => $row['name'],
                'slug' => $row['slug'],
                'summary' => $row['summary'],
                'sort' => $i,
            ]);
        }

        return $activities;
    }

    /** @return array<string, Operator> */
    private function operators(array $rows): array
    {
        $operators = [];
        foreach ($rows as $row) {
            $ratings = collect($row['ratings'])->keyBy('source');
            $operator = Operator::create([
                'name' => $row['name'],
                'slug' => $row['slug'],
                'description' => $row['description'],
                'base_city' => $row['baseCity'],
                'founded_year' => $row['foundedYear'],
                'commission_rate' => $row['commissionRate'],
                'tripadvisor_rating' => $ratings['TripAdvisor']['rating'] ?? null,
                'tripadvisor_reviews' => $ratings['TripAdvisor']['reviews'] ?? null,
                'google_rating' => $ratings['Google']['rating'] ?? null,
                'google_reviews' => $ratings['Google']['reviews'] ?? null,
                'is_active' => true,
                'notes' => 'Demo operator',
            ]);
            foreach ($row['guides'] as $i => $guide) {
                $operator->guides()->create([
                    'name' => $guide['name'],
                    'languages' => $guide['languages'],
                    'note' => $guide['note'],
                    'sort' => $i,
                ]);
            }
            $operators[$row['slug']] = $operator;
        }

        return $operators;
    }

    /** @return array<string, Tour> */
    private function tours(array $rows, array $operators, array $regions, array $activities): array
    {
        $tours = [];
        foreach ($rows as $row) {
            $tour = Tour::create([
                'operator_id' => $operators[$row['operator']]->id,
                'type' => TourType::from($row['type']),
                'status' => TourStatus::Published,
                'title' => $row['title'],
                'slug' => $row['slug'],
                'summary' => $row['summary'],
                'description' => implode("\n\n", $row['description']),
                'duration_days' => $row['durationDays'],
                'difficulty' => $row['difficulty'],
                'difficulty_note' => $row['difficultyNote'],
                'group_size_min' => $row['groupSizeMin'],
                'group_size_max' => $row['groupSizeMax'],
                'guide_languages' => $row['guideLanguages'],
                'route' => $row['route'],
                'max_altitude_m' => $row['maxAltitudeM'] ?? null,
                'season_from' => $row['season']['from'],
                'season_to' => $row['season']['to'],
                'min_age' => $row['minAge'] ?? null,
                'highlights' => $row['highlights'],
                'has_group_dates' => $row['departures'] !== [],
                'has_private_option' => $row['privatePrices'] !== [],
                'commission_rate' => $row['commissionRate'] ?? null,
                'sort_weight' => $row['sortWeight'],
                'published_at' => now(),
            ]);

            foreach ($row['days'] as $day) {
                $tour->days()->create([
                    'day_number' => $day['day'],
                    'title' => $day['title'],
                    'description' => $day['description'],
                    'overnight' => $day['overnight'] ?? null,
                    'meals' => $day['meals'],
                    'activity_hours' => $day['activityHours'] ?? null,
                    'max_altitude_m' => $day['maxAltitudeM'] ?? null,
                ]);
            }
            foreach (['included' => TourItemKind::Included, 'excluded' => TourItemKind::Excluded] as $key => $kind) {
                foreach ($row[$key] as $i => $text) {
                    $tour->items()->create(['kind' => $kind, 'text' => $text, 'sort' => $i]);
                }
            }
            foreach ($row['faqs'] as $i => $faq) {
                $tour->faqs()->create(['question' => $faq['question'], 'answer' => $faq['answer'], 'sort' => $i]);
            }
            foreach ($row['departures'] as $departure) {
                $tour->departures()->create([
                    'starts_on' => $departure['startsOn'],
                    'ends_on' => $departure['endsOn'],
                    'price_cents' => $departure['priceCents'],
                    'seats_total' => $departure['seatsTotal'],
                    'seats_booked' => $departure['seatsBooked'],
                    'status' => DepartureStatus::from($departure['status']),
                ]);
            }
            foreach ($row['privatePrices'] as $price) {
                $tour->privatePrices()->create([
                    'group_size_from' => $price['groupSizeFrom'],
                    'group_size_to' => $price['groupSizeTo'],
                    'price_per_person_cents' => $price['pricePerPersonCents'],
                ]);
            }
            foreach ($row['reviews'] as $review) {
                $tour->reviews()->create([
                    'operator_id' => $tour->operator_id,
                    'source' => ReviewSource::Site,
                    'author_name' => $review['author'],
                    'country' => $review['country'],
                    'rating' => $review['rating'],
                    'body' => $review['body'],
                    'trip_month' => $review['tripMonth'],
                    'is_published' => true,
                    'published_at' => now(),
                ]);
            }

            $tour->regions()->attach(
                collect($row['regions'])->mapWithKeys(fn ($slug, $i) => [$regions[$slug]->id => ['sort' => $i]])->all()
            );
            $tour->activities()->attach(
                collect($row['activities'])->mapWithKeys(fn ($slug, $i) => [$activities[$slug]->id => ['sort' => $i]])->all()
            );

            $tours[$row['slug']] = ['model' => $tour, 'row' => $row];
        }

        return $tours;
    }

    /** The web app defines collections as filters; here they become explicit tour lists. */
    private function collections(array $rows, array $tours): void
    {
        foreach ($rows as $i => $row) {
            $collection = Collection::create([
                'title' => $row['title'],
                'slug' => $row['slug'],
                'is_featured' => true,
                'sort' => $i,
            ]);
            $filter = $row['filter'];
            $matching = collect($tours)
                ->filter(fn ($t) => match (true) {
                    isset($filter['activity']) => in_array($filter['activity'], $t['row']['activities'], true),
                    isset($filter['region']) => in_array($filter['region'], $t['row']['regions'], true),
                    ($filter['difficulty'] ?? null) === 'easy' => $t['row']['difficulty'] <= 2,
                    default => false,
                })
                ->sortByDesc(fn ($t) => $t['row']['sortWeight'])
                ->values();
            $collection->tours()->attach(
                $matching->mapWithKeys(fn ($t, $sort) => [$t['model']->id => ['sort' => $sort]])->all()
            );
        }
    }

    private function posts(array $rows, array $tours): void
    {
        foreach ($rows as $row) {
            $post = Post::create([
                'title' => $row['title'],
                'slug' => $row['slug'],
                'excerpt' => $row['excerpt'],
                'sections' => $row['sections'],
                'facts' => $row['facts'],
                'reading_minutes' => $row['readingMinutes'],
                'published_at' => $row['updatedOn'],
            ]);
            $post->tours()->attach(
                collect($row['tourSlugs'])->mapWithKeys(fn ($slug, $i) => [$tours[$slug]['model']->id => ['sort' => $i]])->all()
            );
        }
    }

    /** Indicative rates until the daily rates job exists (step 4.9). */
    private function currencyRates(): void
    {
        foreach (['USD' => 1, 'EUR' => 0.92, 'GBP' => 0.79, 'AUD' => 1.52] as $code => $rate) {
            CurrencyRate::create(['code' => $code, 'rate_per_usd' => $rate, 'fetched_at' => now()]);
        }
    }
}
