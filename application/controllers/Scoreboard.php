<?php
/**
 * Sharif Judge online judge
 * @file Scoreboard.php
 * @author Mohammad Javad Naderi <mjnaderi@gmail.com>
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Scoreboard extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		if ($this->input->is_cli_request())
			return;
		if ( ! $this->session->userdata('logged_in')) // if not logged in
			redirect('login');

		$this->load->model('scoreboard_model');
	}

	// ------------------------------------------------------------------------

	public function index()
	{
		
		$data = array(
			'scoreboard' => $this->scoreboard_model->get_scoreboard_public()
		);

		$this->twig->display('pages/scoreboard.twig', $data);
	}

	// ------------------------------------------------------------------------

	public function overscore()
	{
		if( $this->user->level <= 2 )
			show_404();

		$data = array(
			'scoreboard' => $this->scoreboard_model->get_scoreboard_admin()
		);

		$this->twig->display('pages/scoreboard.twig', $data);
		return;
	}

	// ------------------------------------------------------------------------

	public function problem($problem_id)
	{
		$data = array(
			'scoreboard' => $this->scoreboard_model->get_scoreboard_problem($problem_id)
		);

		$this->twig->display('pages/scoreboard.twig', $data);
		return;
	}

	// ------------------------------------------------------------------------

	public function user($username = NULL)
	{
		$data = array(
			'scoreboard' => $this->scoreboard_model->get_scoreboard_user($username)
		);

		$this->twig->display('pages/scoreboard.twig', $data);
		return;
	}

	// ------------------------------------------------------------------------
	
	public function update()
	{
		if( $this->user->level <= 2 )
			show_404();

		$this->load->model('scoreboard_model');
		$this->scoreboard_model->update_scoreboards();

		$this->index();
	}

	/**
	 * Uses PHPExcel library to generate excel file of submissions
	 */
	public function download_excel()
	{
		if( $this->user->level <= 2 )
			show_404();

		$now = shj_now_str(); // current time
		// Load PHPExcel library
		$this->load->library('phpexcel');
		// Set document properties
		$this->phpexcel->getProperties()->setCreator('Sharif Judge')
			->setLastModifiedBy('Sharif Judge')
			->setTitle('Sharif Judge Users')
			->setSubject('Sharif Judge Users')
			->setDescription('List of Sharif Judge users ('.$now.')');
		// Name of the file sent to browser
		$output_filename = 'judge_scoreboard';
		// Set active sheet
		$this->phpexcel->setActiveSheetIndex(0);
		$sheet = $this->phpexcel->getActiveSheet();

		// Prepare header
		$header = array('#','Username','Display name','Total score','Total Submissions','Last Submissions','Problem name','Score','Last submissions','Count');
		// Add header to document
		$sheet->fromArray($header, null, 'A1', true);
		$highest_column = $sheet->getHighestColumn();
		// Set custom style for header
		$sheet->getStyle('A1:'.$highest_column.'1')->applyFromArray(
			array(
				'fill' => array(
					'type' => PHPExcel_Style_Fill::FILL_SOLID,
					'color' => array('rgb' => '173C45')
				),
				'font'  => array(
					'bold'  => true,
					'color' => array('rgb' => 'FFFFFF'),
					//'size'  => 14
				)
			)
		);

		$items = $this->scoreboard_model->download_excel();
		$scoreboard = $items['scoreboard'];
		$all_problems = $items['all_problems'];
		$problems = $items['problems'];
		$counts = $items['count'];
		$names = $items['names'];

		$i = 2;
		$num = 0;
		$rows = array();
		foreach ($scoreboard['username'] as $username){
			$row = array(
				++$num,
				$username,
				$items[$username],
				$scoreboard['total_score'][$num-1],
				$scoreboard['total_submissions'][$num-1],
				$scoreboard['last_submissions'][$num-1]
			);
			$end = $i+$counts-1;
			$sheet->mergeCells("A{$i}:A{$end}");
			$sheet->mergeCells("B{$i}:B{$end}");
			$sheet->mergeCells("C{$i}:C{$end}");
			$sheet->mergeCells("D{$i}:D{$end}");
			$sheet->mergeCells("E{$i}:E{$end}");
			$sheet->mergeCells("F{$i}:F{$end}");
			// Add rows to document
			$sheet->fromArray($row, null, 'A'.$i, true);

			$j = 0;
			foreach ($all_problems as $problem) {
				$end = $i + $j;
				$sheet->setCellValue('G'.$end, $problem['name']);
				if( isset($problems[$username][$problem['id']]['score']) )
				{
					$row = array(
						$problems[$username][$problem['id']]['score'],
						$problems[$username][$problem['id']]['last_submissions'],
						$problems[$username][$problem['id']]['submis_count'],
					);
					$sheet->fromArray($row, null, 'H'.$end, true);
					$sheet->getStyle('H'.$end.':J'.$end)->applyFromArray(
						array(
							'fill' => array(
								'type' => PHPExcel_Style_Fill::FILL_SOLID,
								'color' => array('rgb' => ($num%2)?'20C446':'E1E647')
							),
						)
					);
				}
				else
				{
					$concat =  "H{$end}:J{$end}";
					$sheet->mergeCells($concat);
					$sheet->setCellValue('H'.$end, 'No Submissions');
					$sheet->getStyle('H'.$end.':H'.$end)->applyFromArray(
						array(
							'fill' => array(
								'type' => PHPExcel_Style_Fill::FILL_SOLID,
								'color' => array('rgb' => 'FD6864')
							),
							'font'  => array(
								'bold'  => true,
								'color' => array('rgb' => 'FFFFFF')
							)
						)
					);
				}
				$j++;
			}
			$i += $counts;
		}
		// Set text align to center
		$sheet->getStyle( $sheet->calculateWorksheetDimension() )
			->getAlignment()
			->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
			->setVERTICAL(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		// Making columns autosize
		for ($i=2;$i<count($header);$i++)
			$sheet->getColumnDimension(chr(65+$i))->setAutoSize(true);
		// Set Border
		$sheet->getStyle('A2:'.$highest_column.$sheet->getHighestRow())->applyFromArray(
			array(
				'borders' => array(
					'outline' => array(
						'style' => PHPExcel_Style_Border::BORDER_THIN,
						'color' => array('rgb' => '444444'),
					),
				)
			)
		);
		// Send the file to browser
		$ext = 'xlsx';
		if ( ! class_exists('ZipArchive') ) // If class ZipArchive does not exist, export to excel5 instead of excel 2007
			$ext = 'xls';
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="'.$output_filename.'.'.$ext.'"');
		header('Cache-Control: max-age=0');
		$objWriter = PHPExcel_IOFactory::createWriter($this->phpexcel, ($ext==='xlsx'?'Excel2007':'Excel5'));
		$objWriter->save('php://output');
	}
}