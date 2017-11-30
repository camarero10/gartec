<?php
/**
 * @package     CSVI
 * @subpackage  Ratings import
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Rating import.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportRating extends RantaiImportEngine
{
	/**
	 * Rating import
	 *
	 * @var    VirtueMartTableRating
	 * @since  6.0
	 */
	private $ratingTable = null;

	/**
	 * Rating review table.
	 *
	 * @var    VirtueMartTableRatingReview
	 * @since  6.0
	 */
	private $ratingReviewTable = null;

	/**
	 * Rating vote table.
	 *
	 * @var    VirtueMartTableRatingVote
	 * @since  6.0
	 */
	private $ratingVoteTable = null;

	/**
	 * Start the product import process.
	 *
	 * @return  bool  True on success | false on failure.
	 *
	 * @since   6.0
	 */
	public function getStart()
	{
		// Get the product ID
		$this->setState('virtuemart_product_id', $this->helper->getProductId());

		// Process data
		foreach ($this->fields->getData() as $fields)
		{
			foreach ($fields as $name => $details)
			{
				$value = $details->value;

				switch ($name)
				{
					case 'published':
						switch ($value)
						{
							case 'n':
							case 'N':
							case '0':
								$value = 0;
								break;
							default:
								$value = 1;
								break;
						}

						$this->setState($name, $value);
						break;
					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// Reset loaded state
		$this->loaded = true;

		// Required fields are calc_kind, calc_value_mathop, calc_value
		if ($this->getState('virtuemart_product_id', false)
			&& ($this->getState('username', false) || $this->getState('created_by', false)))
		{
			if (!$this->getState('created_by', false))
			{
				if (!$this->findUserId($this->getState('username', false)))
				{
					$this->loaded = false;
				}
			}

			if ($this->loaded)
			{
				// Bind the values
				$this->ratingReviewTable->bind($this->state);

				if ($this->ratingReviewTable->check())
				{
					$this->setState('virtuemart_rating_review_id', $this->ratingReviewTable->virtuemart_rating_review_id);

					// Check if we have an existing item
					if ($this->getState('virtuemart_rating_review_id', 0) > 0 && !$this->template->get('overwrite_existing_data', true))
					{
						$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('product_sku')));
						$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('product_sku')));
						$this->loaded = false;
					}
					else
					{
						// Load the current content data
						$this->ratingReviewTable->load();
						$this->loaded = true;
					}
				}
			}
		}
		else
		{
			$this->loaded = false;

			$this->log->addStats('skipped', JText::_('COM_CSVI_MISSING_REQUIRED_FIELDS'));
		}

		return true;
	}

	/**
	 * Process a record.
	 *
	 * @return  bool  Returns true if all is OK | Returns false if no product SKU or product ID can be found.
	 *
	 * @since   6.0
	 */
	public function getProcessRecord()
	{
		// Check if there is a product ID
		if ($this->loaded)
		{
			if (!$this->getState('virtuemart_rating_review_id', false) && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new rules when user chooses to ignore new rules
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState('product_sku')));
			}
			else
			{
				// Set some basic values
				if (!$this->getState('lastip', false))
				{
					$this->setState('lastip', $_SERVER['SERVER_ADDR']);
				}

				if (!$this->getState('review_rates', false))
				{
					$this->setState('review_rates', $this->getState('vote', 0));
				}

				if (!$this->getState('review_ratingcount', false))
				{
					$this->setState('review_ratingcount', 1);
				}

				if (!$this->getState('review_rating', false))
				{
					$this->setState('review_rating', $this->getState('review_rates') / $this->getState('review_ratingcount'));
				}

				if (!$this->getState('created_on', false))
				{
					$this->setState('created_on', $this->date->toSql());
				}

				// Set the modified date as we are modifying the product
				if (!$this->getState('modified_on', false))
				{
					$this->setState('modified_on', $this->date->toSql());
					$this->setState('modified_by', $this->userId);
				}

				// Bind the data
				$this->ratingReviewTable->bind($this->state);

				// Store the rating reviews
				if ($this->ratingReviewTable->store())
				{
					// Store the rating votes
					$this->ratingVoteTable->bind($this->state);
					$this->ratingVoteTable->check();

					if ($this->ratingVoteTable->store())
					{
						// Update product votes
						$vote = new stdClass;
						$vote->virtuemart_product_id = $this->getState('virtuemart_product_id');
						$vote->created_on = $this->getState('created_on');
						$vote->created_by = $this->getState('created_by');
						$vote->modified_on = $this->getState('modified_on');
						$vote->modified_by = $this->getState('modified_by');

						// Check if an entry already exist
						$query = $this->db->getQuery(true)
							->select($this->db->quoteName('virtuemart_rating_id'))
							->from($this->db->quoteName('#__virtuemart_ratings'))
							->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $this->getState('virtuemart_product_id'));
						$this->db->setQuery($query);
						$vote->virtuemart_rating_id = $this->db->loadResult();

						// Vote exists
						if ($vote->virtuemart_rating_id > 0)
						{
							// Get all the votes
							$query = $this->db->getQuery(true)
								->select($this->db->quoteName('vote'))
								->from($this->db->quoteName('#__virtuemart_rating_votes'))
								->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $this->getState('virtuemart_product_id'));
							$this->db->setQuery($query);
							$ratings = $this->db->loadColumn();

							// Create the new totals
							$vote->ratingcount = count($ratings);
							$vote->rates = array_sum($ratings);
							$vote->rating = $vote->rates / $vote->ratingcount;
						}
						// Vote does not exist
						else
						{
							$vote->rates = $this->getState('vote');
							$vote->rating = $this->getState('vote');
							$vote->ratingcount = 1;
						}

						// Store the ratings
						$this->ratingTable->save($vote);
					}
				}
				else
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_PRODUCT_REVIEW_NOT_ADDED', $this->ratingReviewTable->getError()));
				}
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Load the necessary tables.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function loadTables()
	{
		$this->ratingTable = $this->getTable('Rating');
		$this->ratingReviewTable = $this->getTable('RatingReview');
		$this->ratingVoteTable = $this->getTable('RatingVote');
	}

	/**
	 * Clear the loaded tables.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function clearTables()
	{
		$this->ratingTable->reset();
		$this->ratingReviewTable->reset();
		$this->ratingVoteTable->reset();
	}

	/**
	 * Find the user ID.
	 *
	 * @param   string  $username  The username to find the ID  for.
	 *
	 * @return  bool  True if it exists | False if not.
	 *
	 * @since   6.0
	 */
	private function findUserId($username)
	{
		if ($username)
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__users'))
				->where($this->db->quoteName('username') . ' = ' . $this->db->quote($username));
			$this->db->setQuery($query);
			$created_by = $this->db->loadResult();

			if (!$created_by)
			{
				$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_PRODUCT_REVIEW_NO_USER_ID', $username));

				return false;
			}
			else
			{
				$this->setState('created_by', $created_by);
			}

			return true;
		}
		else
		{
			return false;
		}
	}
}
