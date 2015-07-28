<?php
/**
 * Sharif Judge online judge
 * @file Problems.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Problems extends CI_Controller
{

	private $all_problems;
	private $edit_problem;
	private $edit;
	private $messages;

	// ------------------------------------------------------------------------


	public function __construct()
	{
		parent::__construct();
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');

		$this->all_problems = $this->problem_model->all_problems();
		$this->messages = array();
		$this->edit = FALSE;
	}


	// ------------------------------------------------------------------------


	/**
	 * Displays detail description of given problem
	 *
	 * @param int $problem_id
	 */
	public function index($problem_id = NULL)
	{
		if($problem_id === NULL)
		{
			$data = array(
				'all_problems' => $this->problem_model->all_problems(),
				'messages'=> $this->messages,
			);

			$this->twig->display('pages/problems.twig', $data);
		}
		else
		{
			$problem = $this->problem_model->problem_info($problem_id);

			$data = array(
				'description_problem' => $problem,
				'can_submit' => TRUE,
			);

			$languages = explode(',', $problem['allowed_languages']);

			$problems_root = rtrim($this->settings_model->get_setting('problems_root'),'/');
			$problem_dir = "$problems_root/p{$problem_id}";

			// Find pdf file
			$pdf_dir = "/var/www/html/pdf/p{$problem_id}";
			$pdf_files = glob("$pdf_dir/*.pdf");

			$data['problem'] = array(
				'id' => $problem_id,
				'description' => '<p>Description not found</p>',
				'allowed_languages' => $languages,
				'has_pdf' => $pdf_files != FALSE
			);

			$path = "$problem_dir/desc.html";
			if (file_exists($path))
				$data['problem']['description'] = file_get_contents($path);
			if( $pdf_files )
			{
				$filename = shj_basename($pdf_files[0]);
				$data['problem']['description'] = base_url('pdf/p'.$problem_id.'/'.$filename);
			}

			if($problem['open'] != 1)
				$data['can_submit'] = FALSE;

			$this->twig->display('pages/problem.twig', $data);
		}
	}




	// ------------------------------------------------------------------------


	/**
	 * Edit problem description as html/markdown
	 *
	 * $type can be 'md', 'html', or 'plain'
	 *
	 * @param string $type
	 * @param int $problem_id
	 */
	public function edit($type = 'md', $problem_id = 0)
	{
		if ($type !== 'html' && $type !== 'md' && $type !== 'plain')
			show_404();

		if ($this->user->level <= 1)
			show_404();

		switch($type)
		{
			case 'html':
				$ext = 'html'; break;
			case 'md':
				$ext = 'md'; break;
			case 'plain':
				$ext = 'html'; break;
		}

		$max_id = $this->problem_model->new_problem_id();
		if ( ! is_numeric($problem_id) || $problem_id < 1 || $problem_id >= $max_id)
			show_404();

		$this->form_validation->set_rules('text', 'text' ,''); /* todo: xss clean */
		if ($this->form_validation->run())
		{
			$this->problem_model->save_problem_description($problem_id, $this->input->post('text'), $ext);
			redirect('problems/');
		}

		$data['info'] = $this->problem_model->problem_info($problem_id);
		$data['problem'] = array(
			'id' => $problem_id,
			'description' => ''
		);

		$path = rtrim($this->settings_model->get_setting('problems_root'),'/')."/p{$problem_id}/desc.".$ext;
		if (file_exists($path))
			$data['problem']['description'] = file_get_contents($path);


		$this->twig->display('pages/admin/edit_problem_'.$type.'.twig', $data);

	}


	// ------------------------------------------------------------------------


	/**
	 * Displays detail description of given problem
	 */
	public function add()
	{
		if ($this->user->level <= 1) // permission denied
			show_404();

		$this->load->library('upload');
		
		if ( ! empty($_POST) )
		{
			if ($this->_add()) // add/edit problem
			{
				//if ( ! $this->edit) // if adding problem (not editing)
				//{
				//   goto problems page
					$this->index();
					return;
				//}
			}
		}

		$data = array(
			'edit' => $this->edit,
		);

		if($this->edit)
			$problem_id = $this->edit_problem;
		else
			$problem_id = $this->problem_model->new_problem_id();

		if($this->edit)
		{
			$data['problem'] = $this->problem_model->problem_info($problem_id);
		}
		else
		{
			$names = $this->input->post('name');
			if ($names === NULL){
				$data['problem'] = array(
					'id' => $problem_id,
					'name' => 'Problem',
					'score' => 100,
					'c_time_limit' => 1000,
					'python_time_limit' => 1500,
					'java_time_limit' => 2000,
					'memory_limit' => 50000,
					'allowed_languages' => 'C,C++',
					'diff_cmd' => 'diff',
					'diff_arg' => '-bB',
					'is_upload_only' => 0,
					'open' => 0
				);
			}
			else
			{
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

				$data['problem'] = array(
					'id' => $problem_id,
					'name' => $names,
					'score' => $scores,
					'c_time_limit' => $c_tl,
					'python_time_limit' => $py_tl,
					'java_time_limit' => $java_tl,
					'memory_limit' => $ml,
					'allowed_languages' => $ft,
					'diff_cmd' => $dc,
					'diff_arg' => $da,
					'is_upload_only' => ($uo != NULL)? $uo : 0,
					'open' => ($po != NULL)? $po : 0,
				);
			}
		}

		$this->twig->display('pages/admin/add_problem.twig', $data);
	}



	// ------------------------------------------------------------------------


	/**
	 * Delete problem
	 */
	public function delete($problem_id = FALSE)
	{
		if ($problem_id === FALSE)
			show_404();
		if ($this->user->level <= 1) // permission denied
			show_404();

		$problem = $this->problem_model->problem_info($problem_id);

		if ($problem['id'] === 0)
			show_404();

		if ($this->input->post('delete') === 'delete')
		{
			$this->problem_model->delete_problem($problem_id);
			redirect('problems');
		}

		$data = array(
			'id' => $problem_id,
			'name' => $problem['name']
		);

		$this->twig->display('pages/admin/delete_problem.twig', $data);

	}



	// ------------------------------------------------------------------------


	/**
	 * Add/Edit problem
	 */
	private function _add()
	{

		// Check permission
		if ($this->user->level <= 1) // permission denied
			show_404();

		$this->form_validation->set_rules('name', 'problem name', 'required|max_length[50]');
		$this->form_validation->set_rules('score', 'problem score', 'required|integer');
		$this->form_validation->set_rules('c_time_limit', 'C/C++ time limit', 'required|integer');
		$this->form_validation->set_rules('python_time_limit', 'python time limit', 'required|integer');
		$this->form_validation->set_rules('java_time_limit', 'java time limit', 'required|integer');
		$this->form_validation->set_rules('memory_limit', 'memory limit', 'required|integer');
		$this->form_validation->set_rules('languages', 'languages', 'required');
		$this->form_validation->set_rules('diff_cmd', 'diff command', 'required');
		$this->form_validation->set_rules('diff_arg', 'diff argument', 'required');

		// Validate input data

		if ( ! $this->form_validation->run())
			return FALSE;


		// Preparing variables
		if ($this->edit)
			$the_id = $this->edit_problem;
		else
			$the_id = $this->problem_model->new_problem_id();

		$problems_root = rtrim($this->settings_model->get_setting('problems_root'), '/');
		$problem_dir = "$problems_root/p{$the_id}";



		// Adding/Editing problem in database

		if ( ! $this->problem_model->add_problem($the_id, $this->edit))
		{
			$this->messages[] = array(
				'type' => 'error',
				'text' => 'Error '.($this->edit?'updating':'adding').' problem.'
			);
			return FALSE;
		}

		$this->messages[] = array(
			'type' => 'success',
			'text' => 'Problem '.($this->edit?'updated':'added').' successfully.'
		);

		// Create problem directory
		if ( ! file_exists($problem_dir) )
			mkdir($problem_dir, 0700);

		$pdf_dir = "./pdf/p{$the_id}";

		// Create problem directory
		if ( ! file_exists($pdf_dir) )
			mkdir($pdf_dir, 0777);

		// Upload PDF File of Problem
		$config = array(
			'upload_path' => $pdf_dir,
			'allowed_types' => 'pdf',
		);
		$this->upload->initialize($config);
		$old_pdf_files = glob("$pdf_dir/*.pdf");
		$pdf_uploaded = $this->upload->do_upload("pdf");
		if ($_FILES['pdf']['error'] === UPLOAD_ERR_NO_FILE)
			$this->messages[] = array(
				'type' => 'notice',
				'text' => "Notice: You did not upload any pdf file for problem. If needed, upload by editing problem."
			);
		elseif ( ! $pdf_uploaded)
			$this->messages[] = array(
				'type' => 'error',
				'text' => "Error: Error uploading pdf file of problem: ".$this->upload->display_errors('', '')
			);
		else
		{
			foreach($old_pdf_files as $old_name)
				shell_exec("rm -f $old_name");
			$this->messages[] = array(
				'type' => 'success',
				'text' => 'PDF file uploaded successfully.'
			);
		}


		// Upload Tests (zip file)

		shell_exec('rm -f '.$problems_root.'/*.zip');
		$config = array(
			'upload_path' => $problems_root,
			'allowed_types' => 'zip',
		);
		$this->upload->initialize($config);
		$zip_uploaded = $this->upload->do_upload('tests_desc');
		$u_data = $this->upload->data();
		if ( $_FILES['tests_desc']['error'] === UPLOAD_ERR_NO_FILE )
			$this->messages[] = array(
				'type' => 'notice',
				'text' => "Notice: You did not upload any zip file for tests. If needed, upload by editing problem."
			);
		elseif ( ! $zip_uploaded )
			$this->messages[] = array(
				'type' => 'error',
				'text' => "Error: Error uploading tests zip file: ".$this->upload->display_errors('', '')
			);
		else
			$this->messages[] = array(
				'type' => 'success',
				'text' => "Tests (zip file) uploaded successfully."
			);

		// Extract Tests (zip file)

		if ($zip_uploaded) // if zip file is uploaded
		{
			// Create a temp directory
			$tmp_dir_name = "shj_tmp_directory";
			$tmp_dir = "$problems_root/$tmp_dir_name";
			shell_exec("rm -rf $tmp_dir; mkdir $tmp_dir;");

			// Extract new test cases and descriptions in temp directory
			$this->load->library('unzip');
			$this->unzip->allow(array('in', 'sol', 'pdf'));
			$extract_result = $this->unzip->extract($u_data['full_path'], $tmp_dir);

			// Remove the zip file
			unlink($u_data['full_path']);

			if ( $extract_result )
			{
				// Remove previous test cases and descriptions
				shell_exec("cd $problem_dir; "
					."rm -f *.in; "
					."rm -f *.out;"
				);
				// Copy new test cases from temp dir
				shell_exec("cd $problems_root; cp -R $tmp_dir_name/* p{$the_id};");
				$this->messages[] = array(
					'type' => 'success',
					'text' => 'Tests (zip file) extracted successfully.'
				);
			}
			else
			{
				$this->messages[] = array(
					'type' => 'error',
					'text' => 'Error: Error extracting zip archive.'
				);
				foreach($this->unzip->errors_array() as $msg)
					$this->messages[] = array(
						'type' => 'error',
						'text' => " Zip Extraction Error: ".$msg
					);
			}

			// Remove temp directory
			shell_exec("rm -rf $tmp_dir");
		}


		return TRUE;
	}

	public function edit_problem($problem_id)
	{

		if ($this->user->level <= 1) // permission denied
			show_404();

		$this->edit_problem = $problem_id;
		$this->edit = TRUE;

		// redirect to add function
		$this->add();
	}

	// ------------------------------------------------------------------------

	/**
	 * Used by ajax request (for select assignment from top bar)
	 */
	public function select()
	{
		if ( ! $this->input->is_ajax_request() )
			show_404();
		$this->form_validation->set_rules('assignment_select', 'Assignment', 'required|integer|greater_than[0]');
		if ($this->form_validation->run())
		{
			$this->user->select_assignment($this->input->post('assignment_select'));
			$this->assignment = $this->assignment_model->assignment_info($this->input->post('assignment_select'));
			$json_result = array(
				'done' => 1
			);
		}
		else
			$json_result = array('done' => 0, 'message' => 'Input Error');
		$this->output->set_header('Content-Type: application/json; charset=utf-8');
		echo json_encode($json_result);
	}

	// ------------------------------------------------------------------------

	/**
	 * Download pdf file of an assignment (or problem) to browser
	 */
	public function pdf($problem_id)
	{
		// Find pdf file
		$pdf_dir = "/var/www/html/pdf/p{$problem_id}/*.pdf";
		$pdf_files = glob($pdf_dir);
		if ( ! $pdf_files )
			show_error("File not found");
		// Download the file to browser
		$this->load->helper('download')->helper('file');
		$filename = shj_basename($pdf_files[0]);
		force_download($filename, file_get_contents($pdf_files[0]), TRUE);
	}

}
