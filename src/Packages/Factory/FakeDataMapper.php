<?php

declare(strict_types=1);

namespace AutoGen\Packages\Factory;

use Illuminate\Support\Str;

class FakeDataMapper
{
    /**
     * Field name patterns and their corresponding fake data generators.
     *
     * @var array
     */
    protected array $fieldPatterns = [
        // Email fields
        '/^.*email.*$/i' => '$this->faker->unique()->safeEmail()',
        
        // Name fields
        '/^.*first.*name.*$/i' => '$this->faker->firstName()',
        '/^.*last.*name.*$/i' => '$this->faker->lastName()',
        '/^.*full.*name.*$/i' => '$this->faker->name()',
        '/^name$/i' => '$this->faker->name()',
        '/^.*username.*$/i' => '$this->faker->unique()->userName()',
        
        // Address fields
        '/^.*address.*$/i' => '$this->faker->address()',
        '/^.*street.*$/i' => '$this->faker->streetAddress()',
        '/^.*city.*$/i' => '$this->faker->city()',
        '/^.*state.*$/i' => '$this->faker->state()',
        '/^.*country.*$/i' => '$this->faker->country()',
        '/^.*zip.*code.*$/i' => '$this->faker->postcode()',
        '/^.*postal.*code.*$/i' => '$this->faker->postcode()',
        
        // Phone fields
        '/^.*phone.*$/i' => '$this->faker->phoneNumber()',
        '/^.*mobile.*$/i' => '$this->faker->phoneNumber()',
        '/^.*tel.*$/i' => '$this->faker->phoneNumber()',
        
        // Company fields
        '/^.*company.*$/i' => '$this->faker->company()',
        '/^.*organization.*$/i' => '$this->faker->company()',
        '/^.*business.*$/i' => '$this->faker->company()',
        
        // Web fields
        '/^.*url.*$/i' => '$this->faker->url()',
        '/^.*website.*$/i' => '$this->faker->url()',
        '/^.*domain.*$/i' => '$this->faker->domainName()',
        
        // Text fields
        '/^.*description.*$/i' => '$this->faker->paragraph()',
        '/^.*bio.*$/i' => '$this->faker->paragraph(2)',
        '/^.*about.*$/i' => '$this->faker->paragraph(3)',
        '/^.*content.*$/i' => '$this->faker->paragraphs(3, true)',
        '/^.*comment.*$/i' => '$this->faker->sentence()',
        '/^.*note.*$/i' => '$this->faker->sentence()',
        '/^.*message.*$/i' => '$this->faker->paragraph()',
        
        // Title and slug fields
        '/^.*title.*$/i' => '$this->faker->sentence(4, false)',
        '/^.*headline.*$/i' => '$this->faker->sentence(6, false)',
        '/^.*slug.*$/i' => '$this->faker->slug()',
        
        // Date and time fields
        '/^.*birth.*date.*$/i' => '$this->faker->date()',
        '/^.*birthday.*$/i' => '$this->faker->date()',
        '/^.*hired.*date.*$/i' => '$this->faker->dateTimeBetween(\'-2 years\', \'now\')',
        '/^.*start.*date.*$/i' => '$this->faker->dateTimeBetween(\'-1 year\', \'now\')',
        '/^.*end.*date.*$/i' => '$this->faker->dateTimeBetween(\'now\', \'+1 year\')',
        '/^.*due.*date.*$/i' => '$this->faker->dateTimeBetween(\'now\', \'+3 months\')',
        '/^.*published.*at.*$/i' => '$this->faker->dateTimeBetween(\'-1 year\', \'now\')',
        
        // Financial fields
        '/^.*price.*$/i' => '$this->faker->randomFloat(2, 10, 1000)',
        '/^.*cost.*$/i' => '$this->faker->randomFloat(2, 5, 500)',
        '/^.*amount.*$/i' => '$this->faker->randomFloat(2, 1, 10000)',
        '/^.*salary.*$/i' => '$this->faker->numberBetween(30000, 150000)',
        '/^.*income.*$/i' => '$this->faker->numberBetween(20000, 200000)',
        '/^.*budget.*$/i' => '$this->faker->numberBetween(1000, 50000)',
        
        // Image and file fields
        '/^.*image.*$/i' => '$this->faker->imageUrl(640, 480)',
        '/^.*photo.*$/i' => '$this->faker->imageUrl(400, 300)',
        '/^.*avatar.*$/i' => '$this->faker->imageUrl(200, 200)',
        '/^.*logo.*$/i' => '$this->faker->imageUrl(300, 100)',
        '/^.*file.*$/i' => '$this->faker->word() . \'.pdf\'',
        '/^.*document.*$/i' => '$this->faker->word() . \'.pdf\'',
        
        // Color fields
        '/^.*color.*$/i' => '$this->faker->hexColor()',
        '/^.*colour.*$/i' => '$this->faker->hexColor()',
        
        // Status and category fields
        '/^.*status.*$/i' => '$this->faker->randomElement([\'active\', \'inactive\', \'pending\'])',
        '/^.*category.*$/i' => '$this->faker->word()',
        '/^.*tag.*$/i' => '$this->faker->word()',
        '/^.*type.*$/i' => '$this->faker->word()',
        
        // Numeric fields
        '/^.*age.*$/i' => '$this->faker->numberBetween(18, 80)',
        '/^.*weight.*$/i' => '$this->faker->numberBetween(50, 120)',
        '/^.*height.*$/i' => '$this->faker->numberBetween(150, 200)',
        '/^.*score.*$/i' => '$this->faker->numberBetween(0, 100)',
        '/^.*rating.*$/i' => '$this->faker->numberBetween(1, 5)',
        '/^.*quantity.*$/i' => '$this->faker->numberBetween(1, 100)',
        '/^.*count.*$/i' => '$this->faker->numberBetween(0, 1000)',
        
        // ID fields (foreign keys)
        '/^.*_id$/i' => 'function () { return rand(1, 10); }',
        
        // Boolean patterns
        '/^is_.*$/i' => '$this->faker->boolean()',
        '/^has_.*$/i' => '$this->faker->boolean()',
        '/^can_.*$/i' => '$this->faker->boolean()',
        '/^.*_enabled$/i' => '$this->faker->boolean(70)', // 70% true
        '/^.*_active$/i' => '$this->faker->boolean(80)', // 80% true
    ];

    /**
     * Data type mappings for different database column types.
     *
     * @var array
     */
    protected array $dataTypeMappings = [
        'string' => '$this->faker->word()',
        'text' => '$this->faker->paragraph()',
        'longtext' => '$this->faker->paragraphs(3, true)',
        'mediumtext' => '$this->faker->paragraphs(2, true)',
        'json' => '[]',
        'integer' => '$this->faker->numberBetween(1, 1000)',
        'bigint' => '$this->faker->numberBetween(1, 100000)',
        'smallint' => '$this->faker->numberBetween(1, 100)',
        'tinyint' => '$this->faker->numberBetween(0, 1)',
        'boolean' => '$this->faker->boolean()',
        'decimal' => '$this->faker->randomFloat(2, 1, 1000)',
        'float' => '$this->faker->randomFloat(2, 1, 1000)',
        'double' => '$this->faker->randomFloat(4, 1, 10000)',
        'date' => '$this->faker->date()',
        'datetime' => '$this->faker->dateTime()',
        'timestamp' => '$this->faker->dateTime()',
        'time' => '$this->faker->time()',
        'year' => '$this->faker->year()',
        'enum' => '$this->faker->randomElement([\'option1\', \'option2\', \'option3\'])',
        'uuid' => '$this->faker->uuid()',
    ];

    /**
     * Custom faker providers for specific locales.
     *
     * @var array
     */
    protected array $localeProviders = [
        'de_DE' => [
            '/^.*name.*$/i' => '$this->faker->name()',
            '/^.*city.*$/i' => '$this->faker->city()',
            '/^.*company.*$/i' => '$this->faker->company()',
        ],
        'fr_FR' => [
            '/^.*name.*$/i' => '$this->faker->name()',
            '/^.*city.*$/i' => '$this->faker->city()',
            '/^.*company.*$/i' => '$this->faker->company()',
        ],
        'es_ES' => [
            '/^.*name.*$/i' => '$this->faker->name()',
            '/^.*city.*$/i' => '$this->faker->city()',
            '/^.*company.*$/i' => '$this->faker->company()',
        ],
    ];

    /**
     * Map a database field to appropriate fake data.
     */
    public function mapFieldToFakeData(array $field, string $locale = 'en_US'): string
    {
        $fieldName = $field['name'];
        $fieldType = $field['type'];
        $nullable = $field['nullable'] ?? false;
        
        // First, try locale-specific patterns
        if (isset($this->localeProviders[$locale])) {
            foreach ($this->localeProviders[$locale] as $pattern => $faker) {
                if (preg_match($pattern, $fieldName)) {
                    return $this->wrapNullable($faker, $nullable);
                }
            }
        }
        
        // Then try field name patterns
        foreach ($this->fieldPatterns as $pattern => $faker) {
            if (preg_match($pattern, $fieldName)) {
                return $this->wrapNullable($faker, $nullable);
            }
        }
        
        // Finally, fall back to data type mapping
        $faker = $this->dataTypeMappings[$fieldType] ?? $this->dataTypeMappings['string'];
        
        return $this->wrapNullable($faker, $nullable);
    }

    /**
     * Wrap faker with nullable logic if field allows null.
     */
    protected function wrapNullable(string $faker, bool $nullable): string
    {
        if (!$nullable) {
            return $faker;
        }
        
        // 10% chance of null for nullable fields
        return "\$this->faker->optional(0.9)->passthrough({$faker})";
    }

    /**
     * Generate fake data for file upload fields.
     */
    public function generateFileUploadFaker(array $field): string
    {
        $fieldName = $field['name'];
        
        // Determine file type based on field name
        if (preg_match('/.*image.*|.*photo.*|.*avatar.*|.*logo.*/i', $fieldName)) {
            return '$this->faker->imageUrl(640, 480)';
        }
        
        if (preg_match('/.*video.*/i', $fieldName)) {
            return '$this->faker->word() . \'.mp4\'';
        }
        
        if (preg_match('/.*audio.*/i', $fieldName)) {
            return '$this->faker->word() . \'.mp3\'';
        }
        
        if (preg_match('/.*document.*|.*pdf.*/i', $fieldName)) {
            return '$this->faker->word() . \'.pdf\'';
        }
        
        // Default file
        return '$this->faker->word() . \'.txt\'';
    }

    /**
     * Generate sequence-based fake data for unique fields.
     */
    public function generateSequenceFaker(array $field): string
    {
        $fieldName = $field['name'];
        
        if (preg_match('/.*email.*/i', $fieldName)) {
            return '$this->sequence(fn ($sequence) => "user{$sequence->index}@example.com")';
        }
        
        if (preg_match('/.*username.*/i', $fieldName)) {
            return '$this->sequence(fn ($sequence) => "user{$sequence->index}")';
        }
        
        if (preg_match('/.*code.*|.*number.*/i', $fieldName)) {
            return '$this->sequence(fn ($sequence) => str_pad((string) $sequence->index, 6, "0", STR_PAD_LEFT))';
        }
        
        // Default sequence
        return '$this->sequence(fn ($sequence) => $sequence->index)';
    }

    /**
     * Generate custom faker for enum fields based on database constraints.
     */
    public function generateEnumFaker(array $field, array $enumValues = []): string
    {
        if (empty($enumValues)) {
            // Try to detect common enum patterns
            $fieldName = $field['name'];
            
            if (preg_match('/.*status.*/i', $fieldName)) {
                $enumValues = ['draft', 'published', 'archived'];
            } elseif (preg_match('/.*priority.*/i', $fieldName)) {
                $enumValues = ['low', 'medium', 'high', 'urgent'];
            } elseif (preg_match('/.*role.*/i', $fieldName)) {
                $enumValues = ['user', 'admin', 'moderator'];
            } elseif (preg_match('/.*gender.*/i', $fieldName)) {
                $enumValues = ['male', 'female', 'other'];
            } else {
                $enumValues = ['option1', 'option2', 'option3'];
            }
        }
        
        $valuesString = "'" . implode("', '", $enumValues) . "'";
        return "\$this->faker->randomElement([{$valuesString}])";
    }

    /**
     * Add custom field pattern for specific use cases.
     */
    public function addCustomPattern(string $pattern, string $faker): void
    {
        $this->fieldPatterns[$pattern] = $faker;
    }

    /**
     * Add custom data type mapping.
     */
    public function addCustomDataType(string $type, string $faker): void
    {
        $this->dataTypeMappings[$type] = $faker;
    }

    /**
     * Get all available patterns for debugging.
     */
    public function getPatterns(): array
    {
        return $this->fieldPatterns;
    }

    /**
     * Get all available data type mappings.
     */
    public function getDataTypeMappings(): array
    {
        return $this->dataTypeMappings;
    }
}