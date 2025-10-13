<?php

namespace App\Ai\Mcp\Tools\Utils;

/**
 * Utility class for mapping user-friendly work group types to 4HSE API enum values.
 *
 * This ensures consistency across all work group related tools and provides
 * a centralized place to manage the mapping between Italian/English user terms
 * and the actual API enum values.
 */
class WorkGroupTypeMapper
{
    /**
     * Map user-friendly work group types to API enum values
     */
    private const WORK_GROUP_TYPE_MAPPING = [
        // Italian terms
        "gruppo omogeneo" => "HGROUP",
        "gruppi omogenei" => "HGROUP",
        "fase di lavoro" => "WORK_PLACE",
        "fasi di lavoro" => "WORK_PLACE",
        "posto di lavoro" => "WORK_PLACE",
        "posti di lavoro" => "WORK_PLACE",
        "ambiente di lavoro" => "WORK_PLACE",
        "ambienti di lavoro" => "WORK_PLACE",
        "mansione" => "JOB",
        "mansioni" => "JOB",
        "ruolo lavorativo" => "JOB",
        "ruoli lavorativi" => "JOB",
        "posizione lavorativa" => "JOB",
        "posizioni lavorative" => "JOB",

        // English terms
        "homogeneous group" => "HGROUP",
        "homogeneous groups" => "HGROUP",
        "work phase" => "WORK_PLACE",
        "work phases" => "WORK_PLACE",
        "work place" => "WORK_PLACE",
        "work places" => "WORK_PLACE",
        "workplace" => "WORK_PLACE",
        "workplaces" => "WORK_PLACE",
        "work environment" => "WORK_PLACE",
        "work environments" => "WORK_PLACE",
        "job role" => "JOB",
        "job roles" => "JOB",
        "job" => "JOB",
        "jobs" => "JOB",
        "position" => "JOB",
        "positions" => "JOB",
        "job position" => "JOB",
        "job positions" => "JOB",

        // Direct enum values (case insensitive)
        "hgroup" => "HGROUP",
        "work_place" => "WORK_PLACE",
    ];

    /**
     * Valid API enum values
     */
    public const VALID_ENUM_VALUES = ["HGROUP", "WORK_PLACE", "JOB"];

    /**
     * Schema description constant for PHP attributes
     */
    public const SCHEMA_DESCRIPTION =
        "Work group type (required). Accepts Italian or English terms: " .
        "'Gruppo Omogeneo'/'Homogeneous Group' → HGROUP, " .
        "'Fase di Lavoro'/'Work Phase' → WORK_PLACE, " .
        "'Mansione'/'Job' → JOB. " .
        "These will be automatically mapped to the correct API enum values.";

    /**
     * Map user-friendly work group type to API enum value
     *
     * @param string $userType User input for work group type
     * @return string|null Mapped API enum value or null if not found
     */
    public static function mapWorkGroupType(string $userType): ?string
    {
        $normalizedType = strtolower(trim($userType));
        return self::WORK_GROUP_TYPE_MAPPING[$normalizedType] ?? null;
    }

    /**
     * Check if a work group type is valid (either user-friendly or API enum)
     *
     * @param string $workGroupType Work group type to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidWorkGroupType(string $workGroupType): bool
    {
        $mapped = self::mapWorkGroupType($workGroupType);
        return $mapped !== null;
    }

    /**
     * Get all supported user-friendly work group type terms
     *
     * @return array Array of supported terms
     */
    public static function getSupportedTerms(): array
    {
        return array_keys(self::WORK_GROUP_TYPE_MAPPING);
    }

    /**
     * Get mapping for documentation purposes
     *
     * @return array Associative array of mapping
     */
    public static function getMapping(): array
    {
        return self::WORK_GROUP_TYPE_MAPPING;
    }

    /**
     * Get user-friendly description of supported work group types
     *
     * @return string Formatted description for tool schemas
     */
    public static function getSchemaDescription(): string
    {
        return self::SCHEMA_DESCRIPTION;
    }

    /**
     * Get error message for invalid work group types
     *
     * @param string $invalidType The invalid type that was provided
     * @return string Error message with suggested valid values
     */
    public static function getInvalidTypeErrorMessage(
        string $invalidType,
    ): string {
        $supportedTerms = self::getSupportedTerms();
        $exampleTerms = array_slice($supportedTerms, 0, 6); // Show first 6 as examples

        return "Work group type '{$invalidType}' is not valid. " .
            "Examples of accepted values: " .
            implode(", ", $exampleTerms) .
            " (and " .
            (count($supportedTerms) - 6) .
            " more variations)";
    }
}
