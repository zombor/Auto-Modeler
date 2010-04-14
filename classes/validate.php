<?php

class Validate extends Kohana_Validate
{
	/**
	 * Create a copy of the current validation rules and change the array.
	 *
	 * ##### Example
	 *
	 *     // Initialize the validation library on the $_POST array with the rule 'required' applied to 'field_name'
	 *     $post = Validation::factory($_POST)->add_rules('field_name', 'required');
	 *
	 *     // Here we copy the rule 'required' for 'field_name' and apply it to a new array (field names need to be the same)
	 *     $new_post = $post->copy($new_array);
	 *
	 * @param   array  $array  New array to validate
	 * @return  Validation
	 */
	public function copy(array $array)
	{
		$copy = clone $this;

		$copy->exchangeArray($array);

		return $copy;
	}
}