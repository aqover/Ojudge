<?php
/**
 * Sharif Judge online judge
 * @file Submit_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Submit_model extends CI_Model {

	public function __construct()
	{
		parent::__construct();
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns table row for a specific submission
	 */
	public function get_submission($username, $problem, $submit_id)
	{
		$query = $this->db->get_where('submissions',
			array(
				'username'=>$username,
				'problem'=>$problem,
				'submit_id'=>$submit_id
			)
		);
		if($query->num_rows()!=1)
			return FALSE;
		return $query->row_array();
	}


	// ------------------------------------------------------------------------


	public function get_final_submissions($user_level, $username, $page_number = NULL, $filter_user = NULL, $filter_problem = NULL)
	{
		$arr['is_final'] = 1;
		if ($user_level === 0)// students can only get final submissions of themselves
			$arr['username']=$username;
		elseif ($filter_user !== NULL)
			$arr['username'] = $filter_user;
		if ($filter_problem !== NULL)
			$arr['problem'] = $filter_problem;
		if ($page_number === NULL)
			return $this->db->order_by('time desc, username asc, problem asc')->get_where('submissions', $arr)->result_array();
		else
		{
			$per_page = $this->settings_model->get_setting('results_per_page_final');
			if ($per_page == 0)
				return $this->db->order_by('time desc, username asc, problem asc')->get_where('submissions', $arr)->result_array();
			else
				return $this->db->order_by('time desc, username asc, problem asc')->limit($per_page,($page_number-1)*$per_page)->get_where('submissions', $arr)->result_array();
		}

	}


	// ------------------------------------------------------------------------


	public function get_all_submissions($user_level, $username, $page_number = NULL, $filter_user = NULL, $filter_problem = NULL)
	{
		if ($user_level === 0)
			$arr['username']=$username;
		elseif ($filter_user !== NULL)
			$arr['username'] = $filter_user;
		if ($filter_problem !== NULL)
			$arr['problem'] = $filter_problem;
		
		if(empty($arr))
			$arr['1'] = 1;

		if ($page_number === NULL)
			return $this->db->order_by('time','desc')->get_where('submissions', $arr)->result_array();
		else
		{
			$per_page = $this->settings_model->get_setting('results_per_page_all');
			if ($per_page == 0)
				return $this->db->order_by('time','desc')->get_where('submissions', $arr)->result_array();
			else
				return $this->db->order_by('time','desc')->limit($per_page,($page_number-1)*$per_page)->get_where('submissions', $arr)->result_array();
		}
	}


	// ------------------------------------------------------------------------


	public function count_final_submissions($user_level, $username, $filter_user = NULL, $filter_problem = NULL)
	{
		$arr['is_final'] = 1;
		if ($user_level === 0)
			$arr['username'] = $username;
		elseif ($filter_user !== NULL)
			$arr['username'] = $filter_user;
		if ($filter_problem !== NULL)
			$arr['problem'] = $filter_problem;
		return $this->db->where($arr)->count_all_results('submissions');
	}


	// ------------------------------------------------------------------------


	public function count_all_submissions($user_level, $username, $filter_user = NULL, $filter_problem = NULL)
	{
		if ($user_level === 0)
			$arr['username'] = $username;
		elseif ($filter_user !== NULL)
			$arr['username'] = $filter_user;
		if ($filter_problem !== NULL)
			$arr['problem'] = $filter_problem;

		if(empty($arr))
			$arr['1'] = 1;
		return $this->db->where($arr)->count_all_results('submissions');
	}


	// ------------------------------------------------------------------------


	public function set_final_submission($username, $problem, $submit_id)
	{

		$this->db->where(array(
			'is_final' => 1,
			'username' => $username,
			'problem' => $problem,
		))->update('submissions', array('is_final'=>0));

		$this->db->where(array(
			'username' => $username,
			'problem' => $problem,
			'submit_id' => $submit_id,
		))->update('submissions', array('is_final'=>1));

		return TRUE;
	}


	// ------------------------------------------------------------------------


	/**
	 * add the result of an "upload only" submit to the database
	 */
	public function add_upload_only($submit_info)
	{

		$this->db->where(array(
			'is_final' => 1,
			'username' => $submit_info['username'],
			'problem' => $submit_info['problem'],
		))->update('submissions', array('is_final'=>0));

		$submit_info['is_final'] = 1;
		$submit_info['status'] = 'Uploaded';

		$this->db->insert('submissions', $submit_info);

		$this->db->where(array('id' => $submission['problem']))->update('problems', array('last_submissions'=>$submit_info['time']));

	}

}