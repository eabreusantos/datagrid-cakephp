<?php
/*
* Data Grid Behavior
* Behavior para alimentar Grid no formato de grid datatable.net
* Implementa: Busca, Ordenação, Paginação, Coluna de ações, Callback para formatar dados de cada coluna
* @author: Eduardo Abreu
* @authorEmail: eduardo.abreu.santos@gmail.com
*
*
* Settings
* @array $columns = array(); Nome das colunas do banco que seram exibidas no banco(Só add Model.field quando quiser campos de um relacionamento)
* @bool $actionCol = Seta se havera uma coluna de Ações
* @bols $actionColCallback = informar o nome de uma função que ira permitir add dados (links) na coluna de ações
*
* Ex.: Função de callback de ActionColCallback
*  function nomeDoCallback($data); //Parametro data irá contar um array com os dados da linha atual da grid Ex.: array('id'=>1,'nome'=>'Eduardo');
*	
* Ex.: Função de callback para formataDados   
* function formatGridData($data,$cols); // os dois atributos possuem a msm qtd de indices, faça um loop nas colunas verificando qual colunas que vc quer e altera o $data de acordo com o indice de $cols
*/

class DatagridBehavior extends ModelBehavior{


	/*
	* Colunas a serem exibidas
	*/
	public $columns = array();


	public $indexColumn = 'id';

	public $limitStart = 0;

	public $limitLength = 10;

	public $search = array();

	public $order = array();

	public $totalRecords = 0;

	public $model;


	public function setup(Model $model, $settings = array())
	{
		$this->model = $model;
		//Default Options
		if(!isset($this->settings[$model->alias]))
		{
			$this->settings[$model->alias] = array('actionCol'=>false);

		}

		$this->settings[$model->alias] = array_merge($this->settings[$model->alias],(array) $settings);

		$this->columns = $this->settings[$model->alias]['columns'];
	}


	/*
	*  Seta limit de registros
	*/
	public function limitQueryString()
	{
		$this->limitStart = (!isset($_GET['iDisplayStart']))? $this->limitStart : $_GET['iDisplayStart'];
		$this->limitLength = (!isset($_GET['iDisplayLength']))? $this->limitStart : $_GET['iDisplayLength'];
	}
	/*
	* Seta busca por query string
	*/
	public function searchQueryString()
	{
		//Busca por campo global
		if(isset($_GET['sSearch']) && $_GET['sSearch']!="")
		{
			$searchValue = $_GET['sSearch'];

			for($i=0; $i<count($this->columns);$i++)
			{
				//Verifica se é um campo relacionamento para não dar conflito na busca
				if($this->isRelationshipField($this->columns[$i])){
				
					$this->search['OR'][] = array($this->columns[$i].' LIKE' => '%'.$searchValue.'%');
				
				}else{

					$this->search['OR'][] = array($this->model->alias.'.'.$this->columns[$i].' LIKE' => '%'.$searchValue.'%');
				}
			}
		}

		//Busca por campos individuais
		for($i=0; $i<count($this->columns);$i++)
		{
			if(isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i]=='true' && $_GET['sSearch_'.$i]!='' )
			{
				//Verifica se é um campo relacionamento para não dar conflito na busca
				if($this->isRelationshipField($this->columns[$i])){
					
					$this->search[$this->columns[$i].' LIKE'] = '%'.$_GET['sSearch_'.$i].'%';
				
				}else{

					$this->search[$this->model->alias.'.'.$this->columns[$i].' LIKE'] = '%'.$_GET['sSearch_'.$i].'%';
				}
			}
		}


		return true;
	}

	/*
	* Seta ordenação da Grid através dos parametros via Query String
	*
	*/
	public function orderQueryString()
	{
		if(isset($_GET['iSortCol_0']))
		{
			for($i=0; $i < intval($_GET['iSortingCols']); $i++)
			{
				if($_GET['bSortable_'.intval($_GET['iSortCol_'.$i]) ]=="true")
				{
					if(!$this->isRelationshipField($this->columns[intval($_GET['iSortCol_'.$i])]))
					{
						$this->order[] = array($this->model->alias.'.'.$this->columns[intval($_GET['iSortCol_'.$i])] => $_GET['sSortDir_'.$i]==='asc' ? 'ASC' : 'DESC'); 
					}else{
					$this->order[] = array($this->columns[intval($_GET['iSortCol_'.$i])] => $_GET['sSortDir_'.$i]==='asc' ? 'ASC' : 'DESC'); 
					}
				}
			}
		}
	}


	/*
	* Retorna os campos/colunas
	* 
	*/
	public function getFields()
	{
		return $this->settings[$this->model->alias]['columns'];
	}


	//Retorna Dados para Montar a Grid 
	public function grid(model $Model)
	{

		//Fields
		$fields = $this->getFields();

		//Order
		$this->orderQueryString();

		//Limit
		$this->limitQueryString();

		//Search
		$this->searchQueryString();

		//Contain
		$contain = $this->getRelationshipFields();

		//total de registros no model
		$this->totalRecords = $Model->find('count');
		//Registros
		$rows = $Model->find('all',array('contain'=>$contain,'conditions'=>$this->search,'fields'=>$fields,'order'=>$this->order,'limit'=>$this->limitLength,'offset'=>$this->limitStart));

		//Formata dados
		$data = $this->formatData($Model, $rows);


		return json_encode($data);

	}


	/*
	*  Retorna os campos relacionais das colunas
	*/
	public function getRelationshipFields()
	{
		$fields = array();
		for($i=0;$i<count($this->columns);$i++)
		{
			if(strpos($this->columns[$i], '.') !=FALSE)
			{
				$fields[] = $this->columns[$i];
			}
		}

		return $fields;
	}

	/*
	* Verifica se o campo é relacional
	*/
	public function isRelationshipField($col)
	{
		if(strpos($col, '.')!=FALSE)
		{
			return true;
		}
		return false;
	}


	/*
	* Formata os dados para o formato necessário para criar a Grid
	*/
	public function formatData(Model $Model, $data)
	{
		$this->model = $Model;
		$output = array();
		$output["sEcho"] = (isset($_GET['sEcho']))? intval($_GET['sEcho']) : 0;
		$output["iTotalRecords"] = count($data);
		$output["iTotalDisplayRecords"] = $this->totalRecords;
		$output["aaData"] = array();

		$this->columns = $this->settings[$Model->alias]['columns'];
		foreach($data as $row)
		{
			$columnsData = array();
			for($i=0;$i<count($this->columns);$i++){

				//Verifica se o campo é relacional
				if($this->isRelationshipField($this->columns[$i])){
					
					$relationshipField = explode('.',$this->columns[$i]); 
					$relationModel = $relationshipField[0]; //Model Relacional
					$relationField = $relationshipField[1]; //Campo relacional

					$columnsData[] = (empty($row[$relationModel][$relationField]))? '' : $row[$relationModel][$relationField] ;

				}else{

					$columnsData[] = (empty($row[$this->model->alias][$this->columns[$i]]))? '' : $row[$this->model->alias][$this->columns[$i]] ;					
				}
			}



			//Verifica se terá coluna de ações
			if($this->settings[$this->model->alias]['actionCol']==true)
			{
				if(!isset($this->settings[$this->model->alias]['actionColCallback']))
				{
					throw new Exception("Callback para formar links de ações, não foi informado!", 1);
					
				}
				//Callback para criar os links de Actions
				if(method_exists($this->model, $this->settings[$this->model->alias]['actionColCallback']))
				{
					$method = $this->settings[$this->model->alias]['actionColCallback'];
					$dataMerge = array_combine($this->columns,$columnsData);
					$col = implode(' ',$this->model->$method($dataMerge));
					$columnsData[] = $col;
				}
			}
			
			//Callback para formatar os dados antes de serem exbidos
			if(method_exists($this->model, 'formatGridData')){
				$columnsData = $this->model->formatGridData($columnsData,$this->columns);
			}

			array_push($output["aaData"], $columnsData);
		}
		return $output;
	}


	/*
	* Add condições extra a busca
	* @params @conditions array de condições
	*/
	public function addGridConditions(Model $model, $conditions)
	{
		$this->search[] = $conditions;
		return $this;
	}

	/*
	* Set columns
	*/
	public function setGridColumns(Model $model, $cols)
	{
		$this->columns = $cols;

		return $this;
	}


	/*
	* Set Grid orderQueryString
	*/
	public function setGridOrder(Model $model, $fieldName,$dir)
	{
		$this->order[] = array($fieldName => $dir);

		return $this;
	}





}