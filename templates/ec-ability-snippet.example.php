<?php
/**
 * Example snippet body for ent-companion/snippets-create.
 *
 * Copy into the "code" field without the opening <?php tag.
 * Requires Ent Companion 2.0.0+ (ec_register_agent_abilities helpers).
 */

add_action(
	'wp_abilities_api_init',
	static function (): void {
		ec_register_agent_abilities(
			array(
				array(
					'slug'        => 'example-task',
					'label'       => 'Example task',
					'description' => 'Replace with a bounded, documented operation.',
					'handler'     => 'ec_handler_example_task',
					'permission'  => 'edit_posts',
				),
			)
		);
	}
);

function ec_handler_example_task( array $input = array() ): array {
	return array( 'ok' => true );
}
