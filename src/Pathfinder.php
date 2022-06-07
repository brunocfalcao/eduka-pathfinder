<?php

namespace Eduka\Pathfinder;

use Eduka\Cube\Models\Course;
use Eduka\Cube\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Assesses if the current url is part of a course frontend, or part of the
 * main course backend.
 */
class Pathfinder
{
    public static function __callStatic($method, $args)
    {
        return PathfinderService::new()->{$method}(...$args);
    }
}

class PathfinderService
{
    public function __construct()
    {
        //
    }

    public static function new(...$args)
    {
        return new self(...$args);
    }

    /**
     * Returns a course instance mapped with the current hostname or null
     * in case there is no course detected (meaning the user is not in an
     * url that is mapped to a course frontend url).
     *
     * @return Eduka\Cube\Models\Course|null
     */
    public function course()
    {
        /**
         * Grab a course instance given the current domain name.
         */
        try {
            return $this->schemaExist() ?
                optional(Course::firstWhere('domain', domain(request()->getHost())))
                : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verify if we are in a course frontend, meaning the url maps a saved
     * course url.
     *
     * @return bool;
     */
    public function inFrontend()
    {
        return $this->course() != null;
    }

    /**
     * Verify if we are in the "main" domain, meaning in the url that shows
     * all the courses to be played, etc. It means we are not in a course
     * frontend.
     *
     * @return bool
     */
    public function inBackend()
    {
        return collect(config('eduka-nereus.main.url'))->search(domain());
    }

    /**
     * External means the current url is NOT from a frontend and NOT from the
     * backend.
     *
     * @return bool
     */
    public function fromExternalSource()
    {

        return ! $this->inBackend() && ! $this->inFrontend();
    }

    /**
     * Contextualizes a course. All future calls to know what course is loaded
     * will be answered with this course.
     *
     * @param  Course  $course
     * @param    $register
     * @return void
     */
    public function contextualize(Course $course, $register = true)
    {
        session(['eduka:pathfinder:course' => $course]);
        session(['eduka:pathfinder:contextualized' => true]);

        if ($register) {
            $course->registerProvider();
        }
    }

    /**
     * Decontextualizes a course. All calls henceforth will dynamically verify
     * what course is loaded via the source url.
     *
     * @return void
     */
    public function decontextualize()
    {
        session()->forget('eduka:pathfinder:course');
        session()->forget('eduka:pathfinder:contextualized');
    }

    /**
     * Logic to verify if the course structure exists. It's not about verifying
     * if courses exists, but about to verify if the data structure (e.g. tables)
     * exist in the database.
     *
     * @return bool
     */
    private function schemaExist()
    {
        return Schema::hasTable('courses');
    }
}
