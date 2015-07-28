<?php
/**
 * Sharif Judge online judge
 * @file Assignment_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Problem_model extends CI_Model
{

	public function __construct()
	{
		parent::__construct();
	}

	// ------------------------------------------------------------------------

	/**
	 * Add New Assignment to DB / Edit Existing Assignment
	 *
	 * @param $id
	 * @param bool $edit
	 * @return bool
	 */
	public function add_problem($id, $edit = FALSE)
	{
		// Start Database Transaction
		$this->db->trans_start();

		/* **** Adding problems to "problems" table **** */

		//Now add new problems:
		$names = $this->input->post('name');
		$scores = $this->input->post('score');
		$c_tl = $this->input->post('c_time_limit');
		$py_tl = $this->input->post('python_time_limit');
		$java_tl = $this->input->post('java_time_limit');
		$ml = $this->input->post('memory_limit');
		$ft = $this->input->post('languages');
		$dc = $this->input->post('diff_cmd');
		$da = $this->input->post('diff_arg');
		$uo = $this->input->post('is_upload_only');
		$po = $this->input->post('open');

		$uo = ($uo != NULL)? 1 : 0;
		$po = ($po != NULL)? 1: 0;
		
		$items = explode(',', $ft);
		$ft = '';
		foreach ($items as $item){
			$item = trim($item);
			$item2 = strtolower($item);
			$item = ucfirst($item2);
			if ($item2 === 'python2')
				$item = 'Python 2';
			elseif ($item2 === 'python3')
				$item = 'Python 3';
			elseif ($item2 === 'pdf')
				$item = 'PDF';
			$item2 = strtolower($item);
			if ( ! in_array($item2, array('c','c++','python 2','python 3','java','zip','pdf')))
				continue;
			// If the problem is not Upload-Only, its language should be one of {C,C++,Python 2, Python 3,Java}
			if ( $uo && ! in_array($item2, array('c','c++','python 2','python 3','java')) )
				continue;
			$ft .= $item.",";
		}
		$ft = substr($ft,0,strlen($ft)-1); // remove last ','
		$problem = array(
			'id' => $id,
			'name' => $names,
			'score' => $scores,
			'is_upload_only' => $uo,
			'c_time_limit' => $c_tl,
			'python_time_limit' => $py_tl,
			'java_time_limit' => $java_tl,
			'memory_limit' => $ml,
			'allowed_languages' => $ft,
			'diff_cmd' => $dc,
			'diff_arg' => $da,
			'open' => $po,
		);

		if($edit)
		{
			$this->db->where('id', $id)->update('problems', $problem);
			$this->db->where('id', $id)->update('problems_info', array('id' => $id,'name' => $names));
		}
		else
		{
			$this->db->insert('problems', $problem);
			$this->db->insert('problems_info', array('id' => $id,'name' => $names, 'total_submissions' => 0));
		}

		// Complete Database Transaction
		$this->db->trans_complete();

		return $this->db->trans_status();
	}

	// ------------------------------------------------------------------------

	/**
	 * Delete A Problem
	 *
	 * @param $problem_id
	 */
	public function delete_problem($problem_id)
	{
		$this->db->trans_start();

		// Phase 1: Delete this assignment and its submissions from database
		$this->db->delete('problems', array('id'=>$problem_id));
		$this->db->delete('problems_info', array('id'=>$problem_id));
		$this->db->delete('submissions', array('problem'=>$problem_id));
		/// update scoreboard:
		$this->load->model('scoreboard_model');
		$this->scoreboard_model->update_scoreboards();
		
		$this->db->trans_complete();

		if ($this->db->trans_status())
		{
			// Phase 2: Delete problem's folder (all test cases and submitted codes)
			$cmd = 'rm -rf '.rtrim($this->settings_model->get_setting('problems_root'), '/').'/p'.$problem_id;
			shell_exec($cmd);
		}
	}

	// ------------------------------------------------------------------------

	/**
	 * New Assignment ID
	 *
	 * Finds the smallest integer that can be uses as id for a new assignment
	 *
	 * @return int
	 */
	public function new_problem_id()
	{
		$max = ($this->db->select_max('id', 'max_id')->get('problems')->row()->max_id) + 1;

		$problems_root = rtrim($this->settings_model->get_setting('problems_root'), '/');
		while (file_exists($problems_root.'/p'.$max)){
			$max++;
		}

		return $max;
	}

	// ------------------------------------------------------------------------

	/**
	 * All Problems of an Assignment
	 *
	 * Returns an array containing all problems of given assignment
	 *
	 * @return mixed
	 */
	public function all_problems()
	{
		$result = $this->db->order_by('id')->get_where('problems')->result_array();
		$problems = array();
		foreach ($result as $row)
			$problems[$row['id']] = $row;
		return $problems;
	}

	// ------------------------------------------------------------------------

	/**
	 * Problem Info
	 *
	 * Returns database row for given problem (from given assignment)
	 *
	 * @param $problem_id
	 * @return mixed
	 */
	public function problem_info($problem_id)
	{
		return $this->db->get_where('problems', array('id'=>$problem_id))->row_array();
	}

	// ------------------------------------------------------------------------

	/**
	 * Increase Total Submits
	 *
	 * Increases number of total submits for given assignment by one
	 *
	 * @param $problem_id
	 * @return mixed
	 */
	public function increase_total_submits($problem_id)
	{
		// Get total submits
		$total = $this->db->select('total_submits')->get_where('problems', array('id'=>$problem_id))->row()->total_submits;
		// Save total+1 in DB
		$this->db->where('id', $problem_id)->update('problems', array('total_submits'=>($total+1)));

		// Return new total
		return ($total+1);
	}

	// ------------------------------------------------------------------------

	/**
	 * Set Moss Time
	 *
	 * Updates "Moss Update Time" for given assignment
	 *
	 * @param $assignment_id
	 */
	public function set_moss_time($problem_id)
	{
		$now = shj_now_str();
		$this->db->where('id', $problem_id)->update('assignments', array('moss_update'=>$now));
	}

	// ------------------------------------------------------------------------

	/**
	 * Get Moss Time
	 *
	 * Returns "Moss Update Time" for given assignment
	 *
	 * @param $assignment_id
	 * @return string
	 */
	public function get_moss_time($problem_id)
	{
		$query = $this->db->select('moss_update')->get_where('assignments', array('id'=>$assignment_id));
		if($query->num_rows() != 1) return 'Never';
		return $query->row()->moss_update;
	}

	// ------------------------------------------------------------------------

	/**
	 * Save Problem Description
	 *
	 * Saves (Adds/Updates) problem description (html or markdown)
	 * @param $problem_id
	 * @param $text
	 * @param $type
	 */
	public function save_problem_description($problem_id, $text, $type)
	{
		$problems_root = rtrim($this->settings_model->get_setting('problems_root'), '/');

		if ($type === 'html')
		{
			// Remove the markdown code
			unlink("$problems_root/p{$problem_id}/desc.md");
			// Save the html code
			file_put_contents("$problems_root/p{$problem_id}/desc.html", $text);
		}
		elseif ($type === 'md')
		{
			// We parse markdown using Parsedown library
			$this->load->library('parsedown');
			// Save the markdown code
			file_put_contents("$problems_root/p{$problem_id}/desc.md", $text);
			// Convert markdown to html and save the html
			file_put_contents("$problems_root/p{$problem_id}/desc.html", $this->parsedown->parse($text));
		}

	}
}
