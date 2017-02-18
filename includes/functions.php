<?php

namespace Automattic\WP\Cron_Control;

/**
 * Check if an event is an internal one that the plugin will always run
 */
function is_internal_event( $action ) {
	return Internal_Events::instance()->is_internal_event( $action );
}

/**
 * Check if the current request is to one of the plugin's REST endpoints
 *
 * @param string $type list|run
 *
 * @return bool
 */
function is_rest_endpoint_request( $type = 'list' ) {
	// Which endpoint are we checking
	$endpoint = null;
	switch ( $type ) {
		case 'list' :
			$endpoint = REST_API::ENDPOINT_LIST;
			break;

		case 'run' :
			$endpoint = REST_API::ENDPOINT_RUN;
			break;
	}

	// No endpoint to check
	if ( is_null( $endpoint ) ) {
		return false;
	}

	// Build the full endpoint and check against the current request
	$run_endpoint = sprintf( '%s/%s/%s', rest_get_url_prefix(), REST_API::API_NAMESPACE, $endpoint );

	return in_array( $run_endpoint, parse_request(), true );
}

/**
 * Schedule an event directly, bypassing the plugin's filtering to capture Core's scheduling functions
 *
 * @param int      $timestamp Time event should run
 * @param string   $action    Hook to fire
 * @param array    $args      Array of arguments, such as recurrence and parameters to pass to hook callback
 * @param int|null $job_id    Optional. Job ID to update
 */
function schedule_event( $timestamp, $action, $args, $job_id = null ) {
	Events_Store::instance()->create_or_update_job( $timestamp, $action, $args, $job_id );
}

/**
 * Execute a specific event
 *
 * @param int     $timestamp      Unix timestamp
 * @param string  $action_hashed  md5 hash of the action used when the event is registered
 * @param string  $instance       md5 hash of the event's arguments array, which Core uses to index the `cron` option
 * @param bool    $force          Run event regardless of timestamp or lock status? eg, when executing jobs via wp-cli
 *
 * @return array|\WP_Error
 */
function run_event( $timestamp, $action_hashed, $instance, $force = false ) {
	return Events::instance()->run_event( $timestamp, $action_hashed, $instance, $force );
}

/**
 * Delete an event entry directly, bypassing the plugin's filtering to capture same
 *
 * @param int    $timestamp Time event should run
 * @param string $action    Hook to fire
 * @param string $instance  Hashed version of event's arguments
 */
function delete_event( $timestamp, $action, $instance ) {
	Events_Store::instance()->mark_job_completed( $timestamp, $action, $instance );
}

/**
 * Delete an event by its ID
 *
 * @param int  $id Event ID
 * $param bool $flush_cache Flush internal cacehs
 * @return bool
 */
function delete_event_by_id( $id, $flush_cache = false ) {
	return Events_Store::instance()->mark_job_record_completed( $id, $flush_cache );
}

/**
 * Check if an entry exists for a particular job, and return its ID if requested
 *
 * @param int    $timestamp Time event should run
 * @param string $action    Hook to fire
 * @param string $instance  Hashed version of event's arguments
 * @param bool   $return_id Return job ID instead of boolean indicating job's existence
 *
 * @return bool|int Boolean when fourth parameter is false, integer when true
 */
function event_exists( $timestamp, $action, $instance, $return_id = false ) {
	return Events_Store::instance()->job_exists( $timestamp, $action, $instance, $return_id );
}

/**
 * Retrieve jobs given a set of parameters
 *
 * @param array $args
 * @return array|false
 */
function get_events( $args ) {
	return Events_Store::instance()->get_jobs( $args );
}

/**
 * Retrieve a single event by ID, or by a combination of its timestamp, instance identifier, and either action or the action's hashed representation
 *
 * @param  array $attributes Array of event attributes to query by
 * @return object|false
 */
function get_event_by_attributes( $attributes ) {
	return Events_Store::instance()->get_job( $attributes );
}

/**
 * Count events with a given status
 *
 * @param string $status Status to count
 * @return int|false
 */
function count_events_by_status( $status ) {
	return Events_Store::instance()->count_events_by_status( $status );
}

/**
 * Flush plugin's internal caches
 *
 * FOR INTERNAL USE ONLY - see WP-CLI; all other cache clearance should happen through the `Events_Store` class
 */
function _flush_internal_caches() {
	return wp_cache_delete( Events_Store::CACHE_KEY );
}

/**
 * Prevent event store from creating new entries
 *
 * Should be used sparingly, and followed by a call to resume_event_creation(), during bulk operations
 */
function _suspend_event_creation() {
	Events_Store::instance()->suspend_event_creation();
}

/**
 * Stop discarding events, once again storing them in the table
 */
function _resume_event_creation() {
	Events_Store::instance()->resume_event_creation();
}
