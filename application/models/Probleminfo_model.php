<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Probleminfo_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}

	public function update_last_submissions($problem_id, $time)
	{
		$this->db->where('id', $problem_id)->update('problems_info', array('last_submissions'=>$time));
	}

	public function update_total_submissions($problem_id, $value = 1)
	{
		// Get total submits
		$total = $this->db->select('total_submissions')->get_where('problems_info', array('id'=>$problem_id))->row()->total_submissions;
		// Save total+1 in DB
		$this->db->where('id', $problem_id)->update('problems_info', array('total_submissions'=> ($total+$value)));
	}

}
