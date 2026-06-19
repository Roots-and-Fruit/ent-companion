<?php
/**
 * Example FluentSnippets body for rootsandfruit/snippets-create.
 *
 * Copy into the "code" field without the opening <?php tag.
 * Requires Roots & Fruit Abilities 1.2.0+ (rf_register_agent_abilities helpers).
 */

add_action(
	'wp_abilities_api_init',
	static function (): void {
		rf_register_agent_abilities(
			array(
				array(
					'slug'        => 'example-task',
					'label'       => 'Example task',
					'description' => 'Replace with a bounded, documented operation.',
					'handler'     => 'rf_handler_example_task',
					'permission'  => 'edit_posts',
				),
				// Add more abilities to the same snippet:
				// array(
				//     'slug'       => 'another-task',
				//     'label'      => 'Another task',
				//     'handler'    => 'rf_handler_another_task',
				//     'permission' => 'read',
				//     'readonly'   => true,
				// ),
			)
		);
	}
);

function rf_handler_example_task( array $input = array() ): array {
	return array( 'ok' => true );
}

// function rf_handler_another_task( array $input = array() ): array {
//     return array( 'ok' => true );
// }
