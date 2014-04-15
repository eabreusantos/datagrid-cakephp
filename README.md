#Datagrid Behavior Cakephp

Behavior Cakephp para abstrair o retorno dos dados para montar a grid do site: datatables.net

##Requisitos

- Cakephp 2.*  
- Datatables.net Plugin

##Instalação

Adicione o arquivo DatagridBehavior.php no diretório App/Model/Behavior/.

## Modo de Uso
Digamos que você queira criar uma grid de listagem de usuários.

```
#Add o Behavior ao model
$actsAs = array(
	'Datagrid'=>array(
		'cols'=>array(
		 'id','username','status' #Colunas do banco para exibir na grid
		),
		'actionCol'=>true, #Especifica que a grid tera uma coluna de ações
		'actionColCallback'=>'createGridLinks' #Metodo utilizado para retornar os buttons
	)
);
```

##Relacionamentos
Caso o model tenha relacionamento com outro model, você poderá também exibir campos deste outro model. Ex.:

```
#Add o Behavior ao model
$actsAs = array(
	'Datagrid'=>array(
		'cols'=>array(
		 'id','username','Group.name','status' #Colunas do banco para exibir na grid
		),
		'actionCol'=>true, #Especifica que a grid tera uma coluna de ações
		'actionColCallback'=>'createGridLinks' #Metodo utilizado para retornar os buttons
	)
);
```
##Métodos

###ActionColCallback
O metodo que você especificar nesta opção, receberá um array contendo os dados de uma linha do banco. Este método deve retornar um array de elementos Ex.:

```
#Cria coluna de ações
public function createGridLinks($data)
{
	$links = array();
	$links[] = '<a ref="#">Editar</a>';
	$links[] = '<a ref="#">Excluir</a>';
	return $links;
}
```

###FormatGridData
Este método é utilizado para alterar os dados das linhas da grid. O método recebe dois argumentos. O primeiro é um array contendo as colunas $cols e outro contendo todos os dados $data. Veja abaixo um exemplo.

```
#Cria coluna de ações
public function formatGridData($cols,$data)
{
	for($i=0;$i<count($cols);$i++)
	{
		if($cols[$i]=="id")
		{
			$data[$i] = 'Cod.:'.$data[$i];
		}
	}

	return $data;
}
```

##No Controller
Para retornar os dados na requisição, utilize o seguinte exemplo:

```
public function index(){
	
	if($this->request->is('ajax'))
	{
		//Ajax Json Request Settings
		$this->autoRender = false;
		$this->response->type('json');

		return $this->Model->grid(); //Retorna Json já formatado
	}

}
```
###addGridConditions
Este metodo é utilizado no controller para adicionar condições nas querys da grid. Ex.:

```
public function index(){
	
	if($this->request->is('ajax'))
	{
		//Ajax Json Request Settings
		$this->autoRender = false;
		$this->response->type('json');
		$this->Model->addGridConditions(array('Model.field'=>$val)); #Add condição
		return $this->Model->grid(); //Retorna Json já formatado
	}

}
```



###Feedbacks
Para feedbacks: eduardo.abreu.santos@gmail.com

Obrigado e Abraços!

