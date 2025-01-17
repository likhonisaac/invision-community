<?php
/**
 * @brief		Statistics Chart Extension
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @subpackage	Forums
 * @since		26 Jan 2023
 */

namespace IPS\forums\extensions\core\Statistics;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Statistics Chart Extension
 */
class _TimeSolved extends \IPS\core\Statistics\Chart
{
	/**
	 * @brief	Controller
	 */
	public $controller = 'forums_stats_solved_time';
	
	/**
	 * Render Chart
	 *
	 * @param	\IPS\Http\Url	$url	URL the chart is being shown on.
	 * @return \IPS\Helpers\Chart
	 */
	public function getChart( \IPS\Http\Url $url ): \IPS\Helpers\Chart
	{
		/* Determine minimum date */
		$minimumDate = NULL;
		
		/* We can't retrieve any stats prior to the new tracking being implemented */
		try
		{
			$oldestLog = \IPS\Db::i()->select( 'MIN(time)', 'core_statistics', array( 'type=?', 'solved' ) )->first();
		
			if( !$minimumDate OR $oldestLog < $minimumDate->getTimestamp() )
			{
				$minimumDate = \IPS\DateTime::ts( $oldestLog );
			}
		}
		catch( \UnderflowException $e )
		{
			/* We have nothing tracked, set minimum date to today */
			$minimumDate = \IPS\DateTime::create();
		}
		
		$chart = new \IPS\Helpers\Chart\Database( $url, 'core_statistics', 'time', '', array(
			'isStacked' => FALSE,
			'backgroundColor' => '#ffffff',
			'hAxis' => array('gridlines' => array('color' => '#f5f5f5')),
			'lineWidth' => 1,
			'areaOpacity' => 0.4
			),
			'AreaChart',
			'daily',
			array('start' => $minimumDate, 'end' => 0),
			array(),
		);
		$chart->setExtension( $this );

		$chart->description = \IPS\Member::loggedIn()->language()->addToStack( 'solved_stats_chart_desc' );
		$chart->availableTypes = array('AreaChart', 'ColumnChart', 'BarChart');
		$chart->enableHourly = FALSE;
		$chart->groupBy = 'value_1';
		$chart->title = \IPS\Member::loggedIn()->language()->addToStack('forums_solved_stats_time');
		$chart->where[] = array( "type=?", 'solved' );
		
		foreach( $validForumIds = $this->getValidForumIds() as $forumId => $forum )
		{
			$chart->addSeries( $forum->_title, 'number', 'AVG(value_4) / 3600', TRUE, $forumId );
		}
		
		return $chart;
	}
	
	/**
	 * Get valid forum IDs to protect against bad data when a forum is removed
	 *
	 * @return array
	 */
	protected function getValidForumIds()
	{
		$validForumIds = [];

		foreach( \IPS\Db::i()->select( 'value_1', 'core_statistics', [ 'value_4 IS NOT NULL and type=?', 'solved' ], NULL, [ 0,100 ], 'value_1' ) as $forumId )
		{
			try
			{
				/* @var $forumId int */
				$validForumIds[ $forumId ] = \IPS\forums\Forum::load( $forumId );
			}
			catch( \Exception $e ) { }
		}
		
		return $validForumIds;
	}
}