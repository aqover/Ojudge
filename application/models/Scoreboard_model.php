<?php
/**
 * Sharif Judge online judge
 * @file Scoreboard_model.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Scoreboard_model extends CI_Model
{

	private $total_score;
	private $total_submissions;
	private $last_submissions;
	private $problems;
	private $users;

	private $generate;

	public function __construct()
	{
		parent::__construct();

		$thsi->total_score = array();
		$this->total_submissions = array();
		$this->last_submissions = array();
		$this->problems = array();
		$this->users = array();

		$this->generate = 0;
	}

	public function get_scoreboard_problem($problem_id)
	{
		$query =  $this->db->select('scoreboard')->get_where('scoreboard', array('problem'=>$problem_id, 'username' => NULL));

		if ($query->num_rows() != 1)
				return 'Scoreboard not found';
			else
				return $query->row()->scoreboard;
	}

	public function get_scoreboard_user($username)
	{
		$query =  $this->db->select('scoreboard')->get_where('scoreboard', array('username'=>$username));
		
		if ($query->num_rows() != 1)
				return 'Scoreboard not found';
			else
				return $query->row()->scoreboard;
	}

	public function get_scoreboard_public()
	{
		$query =  $this->db->select('scoreboard')->get_where('scoreboard', array('problem'=>1,'username'=>'shj_public'));
		
		if ($query->num_rows() != 1)
				return 'Scoreboard not found';
			else
				return $query->row()->scoreboard;
	}

	public function get_scoreboard_admin()
	{
		$query =  $this->db->select('scoreboard')->get_where('scoreboard', array('problem'=>3,'username'=>'shj_admin'));
		
		if ($query->num_rows() != 1)
				return 'Scoreboard not found';
			else
				return $query->row()->scoreboard;
	}

	public function update_scoreboards()
	{
		$problems = $this->db->select('id')->get('problems')->result_array();
		foreach ($problems as $problem){
			$this->update_scoreboard($problem['id'], 1);
		}

		$names = $this->user_model->get_all_users();
		foreach ($names as $name){
			$this->update_user($name['username']);
		}
	}

	public function update_scoreboard($problem_id, $type = 0)
	{
		if(! $this->generate)
			$this->_generate_scoreboard();

		$this->update_admin();
 		$this->update_public();
 		$this->update_problem($problem_id);
 		if($type == 0)
 			$this->update_user($this->user->username);
	}

	public function download_excel()
	{
		if(! $this->generate)
			$this->_generate_scoreboard();
		
		$scoreboard = array(
			'username' => array(),
			'total_score' => array(),
			'total_submissions' => array(),
			'last_submissions' => array()
		);

		foreach($this->users as $username){
			array_push($scoreboard['username'], $username);
			array_push($scoreboard['total_score'], $this->total_score[$username]);
			array_push($scoreboard['total_submissions'], $this->total_submissions[$username]);
			array_push($scoreboard['last_submissions'], $this->last_submissions[$username]);
		}

		array_multisort(
			$scoreboard['total_score'], SORT_NUMERIC, SORT_DESC,
			$scoreboard['total_submissions'], SORT_NUMERIC, SORT_ASC,
			$scoreboard['last_submissions'], SORT_REGULAR, SORT_ASC,
			$scoreboard['username']
		);
		$all_problems = $this->problem_model->all_problems();
		$total_score = 0;
		foreach($all_problems as $i)
			$total_score += $i['score'];
		$data = array(
			'count' => count($all_problems),
			'names' => $this->user_model->get_names(),
			'all_problems' => $all_problems,
			'total_score' => $total_score,
			'problems' => $this->problems,
			'scoreboard' => $scoreboard
		);

		return $data;
	}

	private function update_admin()
	{
		$scoreboard = array(
			'username' => array(),
			'total_score' => array(),
			'total_submissions' => array(),
			'last_submissions' => array()
		);

		foreach($this->users as $username){
			array_push($scoreboard['username'], $username);
			array_push($scoreboard['total_score'], $this->total_score[$username]);
			array_push($scoreboard['total_submissions'], $this->total_submissions[$username]);
			array_push($scoreboard['last_submissions'], $this->last_submissions[$username]);
		}

		array_multisort(
			$scoreboard['total_score'], SORT_NUMERIC, SORT_DESC,
			$scoreboard['total_submissions'], SORT_NUMERIC, SORT_ASC,
			$scoreboard['last_submissions'], SORT_REGULAR, SORT_ASC,
			$scoreboard['username']
		);
		$all_problems = $this->problem_model->all_problems();
		$total_score = 0;
		foreach($all_problems as $i)
			$total_score += $i['score'];
		$data = array(
			'count' => count($all_problems),
			'names' => $this->user_model->get_names(),
			'all_problems' => $all_problems,
			'total_score' => $total_score,
			'problems' => $this->problems,
			'scoreboard' => $scoreboard
		);

		$scoreboard_table = $this->twig->render('pages/scoreboard/scoreboard_table_admin.twig', $data);

		$query = $this->db->select('problem')->get_where('scoreboard', array('problem'=> 3, 'username'=>'shj_admin'));
		if ($query->num_rows()==0)
			$this->db->insert('scoreboard', array('problem'=> 3, 'username'=>'shj_admin', 'scoreboard'=>$scoreboard_table));
		else
			$this->db->where('username', 'shj_admin')->update('scoreboard', array('scoreboard'=>$scoreboard_table));
	}

	private function update_public()
	{
		$scoreboard = array(
			'username' => array(),
			'total_score' => array(),
			'total_submissions' => array(),
			'last_submissions' => array()
		);

		foreach($this->users as $username){
			array_push($scoreboard['username'], $username);
			array_push($scoreboard['total_score'], $this->total_score[$username]);
			array_push($scoreboard['total_submissions'], $this->total_submissions[$username]);
			array_push($scoreboard['last_submissions'], $this->last_submissions[$username]);
		}

		array_multisort(
			$scoreboard['total_score'], SORT_NUMERIC, SORT_DESC,
			$scoreboard['total_submissions'], SORT_NUMERIC, SORT_ASC,
			$scoreboard['last_submissions'], SORT_REGULAR, SORT_ASC,
			$scoreboard['username']
		);
		$data = array(
			'names' => $this->user_model->get_names(),
			'scoreboard' => $scoreboard
		);

		$scoreboard_table = $this->twig->render('pages/scoreboard/scoreboard_table_public.twig', $data);

		$query = $this->db->select('problem')->get_where('scoreboard', array('problem'=> 1, 'username'=>'shj_public'));
		if ($query->num_rows()==0)
			$this->db->insert('scoreboard', array('problem'=> 1, 'username'=>'shj_public', 'scoreboard'=>$scoreboard_table));
		else
			$this->db->where('username','shj_public')->update('scoreboard', array('scoreboard'=>$scoreboard_table));
	}

	private function update_problem($problem_id)
	{
		$scoreboard = array(
			'username' => array(),
			'score' => array(),
			'submis_count' => array(),
			'last_submissions' => array()
		);


		foreach($this->users as $username){
			if(! isset($this->problems[$username][$problem_id]['score']))
				continue;
			array_push($scoreboard['username'], $username);
			array_push($scoreboard['score'], $this->problems[$username][$problem_id]['score']);
			array_push($scoreboard['submis_count'], $this->problems[$username][$problem_id]['submis_count']);
			array_push($scoreboard['last_submissions'], $this->problems[$username][$problem_id]['last_submissions']);
		}

		array_multisort(
			$scoreboard['score'], SORT_NUMERIC, SORT_DESC,
			$scoreboard['submis_count'], SORT_NUMERIC, SORT_ASC,
			$scoreboard['last_submissions'], SORT_REGULAR, SORT_ASC,
			$scoreboard['username']
		);
		$data = array(
			'names' => $this->user_model->get_names(),
			'scoreboard' => $scoreboard
		);

		$scoreboard_table = $this->twig->render('pages/scoreboard/scoreboard_table_problem.twig', $data);

		$query = $this->db->select('problem')->get_where('scoreboard', array('problem'=> $problem_id, 'username' => NULL));
		if ($query->num_rows()==0)
			$this->db->insert('scoreboard', array('problem'=> $problem_id, 'username' => NULL, 'scoreboard'=>$scoreboard_table));
		else
			$this->db->where('problem', $problem_id)->where('username', NULL)->update('scoreboard', array('scoreboard'=>$scoreboard_table));
	}

	private function update_user($username)
	{
		$data = array(
			'all_problems' =>  $this->problem_model->all_problems(),
			'problems' => $this->problems[$username]
		);

		$scoreboard_table = $this->twig->render('pages/scoreboard/scoreboard_table_user.twig', $data);

		$query = $this->db->select('username')->get_where('scoreboard', array('username'=> $username, 'problem' => 0));
		if ($query->num_rows()==0)
			$this->db->insert('scoreboard', array('username'=> $username,'problem' => 0, 'scoreboard'=>$scoreboard_table));
		else
			$this->db->where('username', $username)->where('problem', '0')->update('scoreboard', array('scoreboard'=>$scoreboard_table));
	}

	private function _generate_scoreboard()
	{
		$pi = $this->problem_model->all_problems();
		$submissions = $this->db->get_where('submissions', array('is_final' => 1))->result_array();

		$problems = array();
		$total_score = array();
		$total_submissions = array();
		$last_submissions = array();
		$users = array();

		foreach ($submissions as $submission){

			$time = (string)$submission['time'];
			$problems[$submission['username']][$submission['problem']]['last_submissions'] = $time;

			if(! isset($last_submissions[$submission['username']]) )
				$last_submissions[$submission['username']] = $time;

			if($last_submissions[$submission['username']] < $time)
				$last_submissions[$submission['username']] = $time;
			
			$final_score = ceil($submission['pre_score']*$pi[$submission['problem']]['score']/10000);
			$problems[$submission['username']][$submission['problem']]['score'] = $final_score;

			if(! isset($total_score[$submission['username']]) )
				$total_score[$submission['username']] = 0;
			$total_score[$submission['username']] += $final_score;

			$submis_count = $this->db->where(array('problem' => $submission['problem'],'username' => $submission['username']))->count_all_results('submissions');
			$problems[$submission['username']][$submission['problem']]['submis_count'] = $submis_count;

			if(! isset($total_submissions[$submission['username']]) )
				$total_submissions[$submission['username']] = 0;
			$total_submissions[$submission['username']] += $submis_count;

			$users[] = $submission['username'];
		}

		$users = array_unique($users);

		$this->total_score = $total_score;
		$this->total_submissions = $total_submissions;
		$this->last_submissions = $last_submissions;
		$this->problems = $problems;
		$this->users = $users;

		$this->generate = 1;
	}

}
