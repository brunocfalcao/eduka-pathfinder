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
     * Returns the current request hostname.
     *
     * @param  Request $request
     * @return string|null
     */
    protected function host()
    {
        $host = collect(explode('.', request()->getHost()));

        if ($host->first() == 'www') {
            $host->shift();
        }

        return $host->join('.');
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
        if (session()->has('eduka-pathfinder-contextualized')) {
            return session('eduka-pathfinder-course');
        }

        return $this->exist() ?
                optional(Domain::firstWhere('name', $this->host()))->course
                : null;
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
        return $this->host() == config('eduka-Pathfinder.main.url');
    }

    /**
     * External means the url source is NOT from a frontend and NOT from the
     * backend.
     *
     * @return bool
     */
    public function isExternal()
    {
        return ! $this->inBackend() && ! $this->inFrontend();
    }

    /**
     * Contextualizes a course. All future calls to know what course is loaded
     * will be answered with this course.
     *
     * @param  Course $course
     * @return void
     */
    public function contextualize(Course $course)
    {
        session(['eduka-pathfinder-course' => $course]);
        session(['eduka-pathfinder-contextualized' => true]);
    }

    /**
     * Decontextualizes a course. All calls henceforth will dynamically verify
     * what course is loaded via the source url.
     *
     * @return void
     */
    public function decontextualize()
    {
        session()->forget('eduka-pathfinder-course');
        session()->forget('eduka-pathfinder-contextualized');
    }

    /**
     * Logic to verify if the course structure exists. It's not about verifying
     * if courses exists, but about to verify if the data structure (e.g. tables)
     * exist in the database.
     *
     * @return bool
     */
    private function exist()
    {
        return Schema::hasTable('courses') && Schema::hasTable('domains');
    }
}
