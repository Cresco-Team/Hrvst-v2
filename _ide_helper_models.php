<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models\Address{
/**
 * @property int $id
 * @property int $municipality_id
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profiles\FarmerProfile> $farmers
 * @property-read int|null $farmers_count
 * @property-read \App\Models\Address\Municipality $municipality
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Barangay newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Barangay newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Barangay query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Barangay whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Barangay whereMunicipalityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Barangay whereName($value)
 */
	class Barangay extends \Eloquent {}
}

namespace App\Models\Address{
/**
 * @property int $id
 * @property int $province_id
 * @property string $name
 * @property numeric $latitude
 * @property numeric $longitude
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Address\Barangay> $barangays
 * @property-read int|null $barangays_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profiles\FarmerProfile> $farmers
 * @property-read int|null $farmers_count
 * @property-read \App\Models\Address\Province $province
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Municipality newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Municipality newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Municipality query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Municipality whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Municipality whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Municipality whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Municipality whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Municipality whereProvinceId($value)
 */
	class Municipality extends \Eloquent {}
}

namespace App\Models\Address{
/**
 * @property int $id
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profiles\FarmerProfile> $farmers
 * @property-read int|null $farmers_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Address\Municipality> $municipalities
 * @property-read int|null $municipalities_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Province newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Province newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Province query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Province whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Province whereName($value)
 */
	class Province extends \Eloquent {}
}

namespace App\Models\Interaction{
/**
 * @property int $id
 * @property int $user_id
 * @property int $post_id
 * @property string $created_at
 * @property-read \App\Models\Marketplace\Post|null $post
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostHeart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostHeart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostHeart query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostHeart whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostHeart whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostHeart wherePostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostHeart whereUserId($value)
 */
	class PostHeart extends \Eloquent {}
}

namespace App\Models\Interaction{
/**
 * @property int $id
 * @property int $user_id
 * @property int $variety_id
 * @property string $created_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Product\Variety $variety
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VarietyHeart newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VarietyHeart newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VarietyHeart query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VarietyHeart whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VarietyHeart whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VarietyHeart whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VarietyHeart whereVarietyId($value)
 */
	class VarietyHeart extends \Eloquent {}
}

namespace App\Models\Marketplace{
/**
 * @property int $id
 * @property int $user_id
 * @property int $vegetable_id
 * @property \App\Enums\PostType $type
 * @property \App\Enums\PostStatus $status
 * @property string|null $target_month
 * @property \Carbon\CarbonImmutable|null $scheduled_date
 * @property \App\Enums\PostTimeSlot|null $time_slot
 * @property numeric|null $estimated_total_weight
 * @property-read int|null $hearts_count
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Profiles\DealerProfile|null $dealerProfile
 * @property-read \App\Models\Profiles\FarmerProfile|null $farmerProfile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Interaction\PostHeart> $hearts
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Marketplace\PostItem> $postItems
 * @property-read int|null $post_items_count
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Product\Vegetable $vegetable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post demand()
 * @method static \Database\Factories\Marketplace\PostFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post growing()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post harvested()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post supply()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereEstimatedTotalWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereHeartsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereScheduledDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereTargetMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereTimeSlot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post whereVegetableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Post withoutTrashed()
 */
	class Post extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models\Marketplace{
/**
 * @property int $id
 * @property int $post_id
 * @property int $variety_id
 * @property numeric $quantity_kg
 * @property numeric|null $unit_price
 * @property \App\Enums\PostPriceFlag $price_flag
 * @property \App\Enums\PostItemStatus $status
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Marketplace\Post|null $post
 * @property-read \App\Models\Product\Variety $variety
 * @method static \Database\Factories\Marketplace\PostItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem fulfilled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem ofStatus(\App\Enums\PostItemStatus $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem ongoing()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem unsettled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem wherePostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem wherePriceFlag($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem whereQuantityKg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem whereVarietyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItem withoutTrashed()
 */
	class PostItem extends \Eloquent {}
}

namespace App\Models\Product{
/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product\Vegetable> $vegetables
 * @property-read int|null $vegetables_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Category whereSlug($value)
 */
	class Category extends \Eloquent {}
}

namespace App\Models\Product{
/**
 * @property int $id
 * @property int $variety_id
 * @property numeric $price_min
 * @property numeric $price_max
 * @property \Carbon\CarbonImmutable $recorded_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Product\Variety $variety
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceHistory wherePriceMax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceHistory wherePriceMin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceHistory whereRecordedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PriceHistory whereVarietyId($value)
 */
	class PriceHistory extends \Eloquent {}
}

namespace App\Models\Product{
/**
 * @property int $id
 * @property int $vegetable_id
 * @property string $name
 * @property-read int|null $hearts_count
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Interaction\VarietyHeart> $hearts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product\PriceHistory> $lastTwoPrices
 * @property-read int|null $last_two_prices_count
 * @property-read \App\Models\Product\PriceHistory|null $latestPrice
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Marketplace\PostItem> $postItems
 * @property-read int|null $post_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product\PriceHistory> $prices
 * @property-read int|null $prices_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product\PriceHistory> $recentPrices
 * @property-read int|null $recent_prices_count
 * @property-read \App\Models\Product\Vegetable $vegetable
 * @method static \Database\Factories\Product\VarietyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variety newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variety newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variety query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variety search(?string $search)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variety whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variety whereHeartsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variety whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variety whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variety whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Variety whereVegetableId($value)
 */
	class Variety extends \Eloquent {}
}

namespace App\Models\Product{
/**
 * @property-read \App\Models\Product\Variety|null $variety
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VarietyMonthlyStat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VarietyMonthlyStat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VarietyMonthlyStat query()
 */
	class VarietyMonthlyStat extends \Eloquent {}
}

namespace App\Models\Product{
/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Product\Category $category
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Marketplace\Post> $posts
 * @property-read int|null $posts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product\Variety> $varieties
 * @property-read int|null $varieties_count
 * @method static \Database\Factories\Product\VegetableFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vegetable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vegetable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vegetable query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vegetable search(?string $search)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vegetable whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vegetable whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vegetable whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vegetable whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vegetable whereUpdatedAt($value)
 */
	class Vegetable extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models\Profiles{
/**
 * @property int $id
 * @property int $user_id
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Marketplace\PostItem> $demandItems
 * @property-read int|null $demand_items_count
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Marketplace\Post> $posts
 * @property-read int|null $posts_count
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\Profiles\DealerProfileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DealerProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DealerProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DealerProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DealerProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DealerProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DealerProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DealerProfile whereUserId($value)
 */
	class DealerProfile extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models\Profiles{
/**
 * @property int $id
 * @property int $user_id
 * @property int $province_id
 * @property int $municipality_id
 * @property int $barangay_id
 * @property float $latitude
 * @property float $longitude
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Address\Barangay $barangay
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \App\Models\Address\Municipality $municipality
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Marketplace\Post> $posts
 * @property-read int|null $posts_count
 * @property-read \App\Models\Address\Province $province
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Marketplace\PostItem> $supplyItems
 * @property-read int|null $supply_items_count
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\Profiles\FarmerProfileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FarmerProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FarmerProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FarmerProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FarmerProfile whereBarangayId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FarmerProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FarmerProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FarmerProfile whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FarmerProfile whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FarmerProfile whereMunicipalityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FarmerProfile whereProvinceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FarmerProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FarmerProfile whereUserId($value)
 */
	class FarmerProfile extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models\Profiles{
/**
 * @property int $id
 * @property string $name
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 */
	class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property bool $must_change_pin
 * @property string $phone_number
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property \Carbon\CarbonImmutable|null $two_factor_confirmed_at
 * @property-read \App\Models\Profiles\DealerProfile|null $dealerProfile
 * @property-read \App\Models\Profiles\FarmerProfile|null $farmerProfile
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Marketplace\Post> $posts
 * @property-read int|null $posts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Profiles\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereMustChangePin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

