<?php

/**
 * Test stub for the optional ip2location/ip2location-php package, so
 * IP2LocationProvider can be unit-tested without installing that dependency
 * (which also needs ext-bcmath). Only defined when the real class is absent.
 */

namespace IP2Location;

if (!class_exists(Database::class, false)) {
    class Database
    {
        public const ALL = 1;
        public const FILE_IO = 1;

        /** @var array<string, mixed>|false The real library returns array|false. */
        private $record = [];

        public function __construct($path = null, $mode = null)
        {
        }

        /**
         * @param array<string, mixed>|false $record
         */
        public function setRecord($record): void
        {
            $this->record = $record;
        }

        public function lookup($ip, $mode)
        {
            return $this->record;
        }
    }
}
