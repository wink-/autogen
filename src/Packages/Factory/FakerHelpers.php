<?php

declare(strict_types=1);

namespace AutoGen\Packages\Factory;

use Faker\Generator as Faker;
use Illuminate\Support\Str;

class FakerHelpers
{
    /**
     * Generate realistic user data combinations.
     */
    public static function userProfile(Faker $faker): array
    {
        $firstName = $faker->firstName();
        $lastName = $faker->lastName();
        $username = strtolower($firstName . $lastName . $faker->numberBetween(1, 999));
        
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $firstName . ' ' . $lastName,
            'username' => $username,
            'email' => $username . '@' . $faker->safeEmailDomain(),
        ];
    }

    /**
     * Generate realistic company data.
     */
    public static function companyProfile(Faker $faker): array
    {
        $company = $faker->company();
        
        return [
            'name' => $company,
            'slug' => Str::slug($company),
            'email' => 'info@' . strtolower(str_replace(' ', '', $company)) . '.com',
            'phone' => $faker->phoneNumber(),
            'website' => 'https://www.' . strtolower(str_replace(' ', '', $company)) . '.com',
        ];
    }

    /**
     * Generate realistic address data.
     */
    public static function fullAddress(Faker $faker): array
    {
        return [
            'street_address' => $faker->streetAddress(),
            'city' => $faker->city(),
            'state' => $faker->state(),
            'postal_code' => $faker->postcode(),
            'country' => $faker->country(),
            'full_address' => $faker->address(),
        ];
    }

    /**
     * Generate realistic product data.
     */
    public static function productData(Faker $faker): array
    {
        $name = $faker->words(3, true);
        $price = $faker->randomFloat(2, 10, 1000);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $faker->paragraph(3),
            'price' => $price,
            'cost' => $price * 0.6, // 40% markup
            'sku' => strtoupper($faker->bothify('??###??')),
            'weight' => $faker->randomFloat(2, 0.1, 50),
        ];
    }

    /**
     * Generate realistic blog post data.
     */
    public static function blogPost(Faker $faker): array
    {
        $title = $faker->sentence(6, false);
        
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => $faker->paragraph(1),
            'content' => $faker->paragraphs(5, true),
            'reading_time' => $faker->numberBetween(2, 15),
            'published_at' => $faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Generate realistic financial data.
     */
    public static function financialData(Faker $faker): array
    {
        return [
            'amount' => $faker->randomFloat(2, 0.01, 999999.99),
            'currency' => $faker->currencyCode(),
            'transaction_id' => $faker->uuid(),
            'reference_number' => $faker->bothify('TXN-####-????'),
        ];
    }

    /**
     * Generate realistic social media data.
     */
    public static function socialMediaProfile(Faker $faker): array
    {
        $username = $faker->userName();
        
        return [
            'username' => $username,
            'display_name' => $faker->name(),
            'bio' => $faker->paragraph(2),
            'follower_count' => $faker->numberBetween(0, 10000),
            'following_count' => $faker->numberBetween(0, 1000),
            'avatar_url' => $faker->imageUrl(200, 200, 'people'),
            'cover_url' => $faker->imageUrl(800, 300),
        ];
    }

    /**
     * Generate realistic inventory data.
     */
    public static function inventoryData(Faker $faker): array
    {
        $inStock = $faker->boolean(80);
        
        return [
            'quantity' => $inStock ? $faker->numberBetween(1, 100) : 0,
            'reserved_quantity' => $faker->numberBetween(0, 10),
            'reorder_level' => $faker->numberBetween(5, 20),
            'location' => $faker->bothify('??##'),
            'last_counted_at' => $faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Generate realistic order data.
     */
    public static function orderData(Faker $faker): array
    {
        $subtotal = $faker->randomFloat(2, 10, 500);
        $taxRate = 0.08;
        $tax = $subtotal * $taxRate;
        $shipping = $faker->randomFloat(2, 0, 25);
        
        return [
            'order_number' => $faker->bothify('ORD-####-####'),
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'shipping_amount' => $shipping,
            'total_amount' => $subtotal + $tax + $shipping,
            'status' => $faker->randomElement(['pending', 'processing', 'shipped', 'delivered']),
        ];
    }

    /**
     * Generate realistic event data.
     */
    public static function eventData(Faker $faker): array
    {
        $startDate = $faker->dateTimeBetween('now', '+6 months');
        $endDate = $faker->dateTimeBetween($startDate, $startDate->format('Y-m-d H:i:s') . ' +4 hours');
        
        return [
            'title' => $faker->sentence(4, false),
            'description' => $faker->paragraph(3),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'location' => $faker->address(),
            'max_attendees' => $faker->numberBetween(10, 500),
            'price' => $faker->randomElement([0, $faker->randomFloat(2, 5, 200)]),
        ];
    }

    /**
     * Generate realistic API key or token.
     */
    public static function apiCredentials(Faker $faker): array
    {
        return [
            'api_key' => 'ak_' . $faker->bothify('????_????????????????????????'),
            'api_secret' => 'as_' . $faker->bothify('????_????????????????????????????????'),
            'access_token' => 'at_' . $faker->bothify('????????????????????????????????'),
            'refresh_token' => 'rt_' . $faker->bothify('????????????????????????????????'),
        ];
    }

    /**
     * Generate realistic subscription data.
     */
    public static function subscriptionData(Faker $faker): array
    {
        $plans = ['basic', 'premium', 'enterprise'];
        $plan = $faker->randomElement($plans);
        $startDate = $faker->dateTimeBetween('-1 year', 'now');
        
        return [
            'plan' => $plan,
            'status' => $faker->randomElement(['active', 'cancelled', 'expired', 'trial']),
            'started_at' => $startDate,
            'expires_at' => $faker->dateTimeBetween($startDate, '+1 year'),
            'trial_ends_at' => $faker->optional(0.3)->dateTimeBetween('now', '+30 days'),
        ];
    }

    /**
     * Generate realistic file metadata.
     */
    public static function fileMetadata(Faker $faker): array
    {
        $extensions = ['pdf', 'doc', 'docx', 'jpg', 'png', 'gif', 'mp4', 'mp3'];
        $extension = $faker->randomElement($extensions);
        
        return [
            'filename' => $faker->word() . '.' . $extension,
            'original_name' => $faker->words(3, true) . '.' . $extension,
            'mime_type' => $faker->mimeType(),
            'size' => $faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'path' => 'uploads/' . $faker->year() . '/' . $faker->month() . '/',
        ];
    }

    /**
     * Generate realistic rating and review data.
     */
    public static function reviewData(Faker $faker): array
    {
        $rating = $faker->numberBetween(1, 5);
        
        return [
            'rating' => $rating,
            'title' => $faker->sentence(4, false),
            'content' => $faker->paragraph(3),
            'helpful_votes' => $faker->numberBetween(0, 50),
            'verified_purchase' => $faker->boolean(70),
        ];
    }
}