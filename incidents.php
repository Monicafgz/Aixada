<?php include "php/inc/header.inc.php" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$language;?>" lang="<?=$language;?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $Text['global_title'] . " - " . $Text['head_ti_incidents'];?></title>

    <link href="js/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/aixcss.css" rel="stylesheet">



    <?php if (isset($_SESSION['dev']) && $_SESSION['dev'] == true ) { ?> 
	    <script type="text/javascript" src="js/jquery/jquery.js"></script>
	    <script type="text/javascript" src="js/bootstrap/js/bootstrap.min.js"></script>
	   	<script type="text/javascript" src="js/aixadautilities/jquery.aixadaXML2HTML.js" ></script>
	   	<script type="text/javascript" src="js/aixadautilities/jquery.aixadaSwitchSection.js" ></script>
	   	
	   	<!--script type="text/javascript" src="js/aixadautilities/jquery.aixadaUtilities.js" ></script-->
   	<?php  } else { ?>
	    <script type="text/javascript" src="js/js_for_incidents.min.js"></script>
    <?php }?>

   
	<script type="text/javascript">

	
	$(function(){

		//loading animation
		$('.loadSpinner').attr('src', "img/ajax-loader-<?=$default_theme;?>.gif"); 


		$('.section').hide();


		$('.change-sec')
			.switchSection("init");

		$('.sec-1').show();



			//.switchSection("changeTo",".sec-1");



		
		
	

		/** 
		 *	resets the incidents form when creating a new one. 
		 */
		function resetDetails(){
			$('#subject, #incidents_text, #ufs_concerned').val('');
			$('#statusSelect option:first').attr('selected',true);
			$('#typeSelect option:first').attr('selected',true);
			$('#prioritySelect option:first').attr('selected',true);
			$('#providerSelect  option:first').attr('selected',true);
			$('#commissionSelect  option:first').attr('selected',true);
			$('#ufs_concerned option:first').attr('selected',true);
			$('#incident_id').val('');
			$('#incident_id_info').html('');

		}

	
	


		$("#tblIncidentsViewOptions")
		.button({
			icons: {
	        	secondary: "ui-icon-triangle-1-s"
			}
	    })
	    /*.menu({
			content: $('#tblIncidentsOptionsItems').html(),	
			showSpeed: 50, 
			width:180,
			flyOut: true, 
			itemSelected: function(item){					//TODO instead of using this callback function make your own menu; if jquerui is updated, this will  not work
				//show hide deactivated products
				var filter = $(item).attr('id');
				$('#tbl_incidents tbody').xml2html('reload',{
					params : 'oper=getIncidentsListing&filter='+filter
				});
				
			}//end item selected 
		});*///end menu


		//print incidents accoring to current incidents template in new window or download as pdf
		$("#btn_print")
		.button({
			icons: {
				primary: "ui-icon-print",
	        	secondary: "ui-icon-triangle-1-s"
			}
	    })/*
	    .menu({
			content: $('#printOptionsItems').html(),	
			showSpeed: 50, 
			width:180,
			flyOut: true, 
			itemSelected: function(item){	
				if ($('input:checkbox[name="bulkAction"][checked="checked"]').length  == 0){
					$.showMsg({
						msg:"<?=$Text['msg_err_noselect'];?>",
						buttons: {
							"<?=$Text['btn_ok'];?>":function(){						
								$(this).dialog("close");
							}
						},
						type: 'warning'});
					return false; 
				}

				var link = $(item).attr('id');

				var idList = "";
				$('input:checkbox[name="bulkAction"][checked="checked"]').each(function(){
						idList += $(this).parents('tr').attr('incidentId')+",";
				});
				idList = idList.substring(0,idList.length-1);
				
				switch (link){
					case "printWindow": 
						printWin = window.open('tpl/<?=$tpl_print_incidents;?>?idlist='+idList);
						printWin.focus();
						printWin.print();
						break;
	
					case "printPDF": 
						window.frames['dataFrame'].window.location = "tpl/<?=$tpl_print_incidents;?>?idlist="+idList+"&asPDF=1&outputFormat=D"; 
						break;
				}
								
			}//end item selected 
		});*///end print menu
		

	
		//bulk actions
		$('input[name=bulkAction]')
			.on('click', function(e){
				e.stopPropagation();
			})
			
		$('#toggleBulkActions')
			.click(function(e){
				if ($(this).is(':checked')){
					$('input:checkbox[name="bulkAction"]').attr('checked','checked');
				} else {
					$('input:checkbox[name="bulkAction"]').attr('checked',false);
				}
				e.stopPropagation();
			});

		

		//download selected as zip
		/*$("#btn_zip").button({
			 icons: {
	        		primary: "ui-icon-suitcase"
	        	}
			 })
    		.click(function(e){
    			if ($('input:checkbox[name="bulkAction"][checked="checked"]').length  == 0){
					$.showMsg({
						msg:"<?=$Text['msg_err_noselect'];?>",
						buttons: {
							"<?=$Text['btn_ok'];?>":function(){						
								$(this).dialog("close");
							}
						},
						type: 'warning'});
    			} else {
        		
        			var orderRow = ''; 								
					$('input:checkbox[name="bulkAction"][checked="checked"]').each(function(){
						orderRow += '<input type="hidden" name="order_id[]" value="'+$(this).parents('tr').attr('orderId')+'"/>';
						orderRow += '<input type="hidden" name="provider_id[]" value="'+$(this).parents('tr').attr('providerId')+'"/>';
						orderRow += '<input type="hidden" name="date_for_order[]" value="'+$(this).parents('tr').attr('dateforOrder')+'"/>';
					});
					$('#submitZipForm').empty().append(orderRow);
					getZippedOrders();
    			}
    		});*/

		
		
		//DELETE incidents
		$('.btn_del_incident')
			.on("click", function(e){
					var incidentId = $(this).parents('tr').attr('incidentId'); 
					$.showMsg({
								msg: '<?php echo $Text['msg_delete_incident'];?>',
								type: 'confirm',
								buttons : {
										"<?php echo $Text['btn_ok'];?>": function() {
											
											$.ajax({
											    type: "POST",
											    url: "php/ctrl/Incidents.php?oper=delIncident&incident_id="+incidentId,
											    success: function(msg){
													resetDetails();
													$('#tbl_incidents tbody').xml2html('reload');
											    	
											    },
											    error : function(XMLHttpRequest, textStatus, errorThrown){
											    	$.showMsg({
														msg:XMLHttpRequest.responseText,
														type: 'error'});
											    }
											}); //end ajax
											$(this).dialog( "close" );
											
									  	}, 
									  	"<?php echo $Text['btn_cancel'];?>":function(){
									  		$(this).dialog( "close" );
											
									  	}
									}
					});

					e.stopPropagation();
			});

		
		//detect form submit and prevent page navigation
		$('form').submit(function() { 

			var dataSerial = $(this).serialize();
			
			var btn = $(this)
    		btn.button('loading')

			
			
			$.ajax({
				    type: "POST",
				    url: "php/ctrl/Incidents.php?oper=mngIncident",
				    data: dataSerial,
				    beforeSend: function(){
				   		$('#editorWrap .loadSpinner').show();
					},
				    success: function(msg){
						//switchTo('overview');
						resetDetails();
				    },
				    error : function(XMLHttpRequest, textStatus, errorThrown){
				    	 //$.updateTips('#incidentsMsg','error','Error: '+XMLHttpRequest.responseText);
				    },
				    complete : function(msg){
				    	btn.button('reset');

				    	$('#tbl_incidents tbody').xml2html('reload');
				    	//$('#editorWrap .loadSpinner').hide();//
				    	
				    	
				    }
			}); //end ajax
			
			return false; 
		});

			
		/**
		 *	incidents
		 */
		$('#tbl_incidents tbody').xml2html('init',{
				url: 'php/ctrl/Incidents.php',
				params : 'oper=getIncidentsListing&filter=past2Month',
				loadOnInit: true, 
				beforeLoad: function(){
					$('.loadSpinner').show();
				},
				complete : function(rowCount){
					//$('#tbl_incidents tbody tr:even').addClass('rowHighlight'); 
					$('.loadSpinner').hide();	
				}
		});


		
		$('#tbl_incidents tbody')
			//click on table row
			.on("click", "tr", function(e){

				resetDetails();
				
				//populate the form
				$(this).children('td[field_name]').each(function(){
					
					var input_name = $(this).attr('field_name');
					var value = $(this).text();

	
					if (input_name == 'incident_id') $('#incident_id_info').html('#'+value);
			
					//set the values of the select boxes
					if (input_name == 'type' || input_name == 'status' || input_name == 'priority' || input_name == 'commission' || input_name == 'provider'){
						$("#"+input_name+"Select").val(value).attr("selected",true);
						
					} else if (input_name == 'ufs_concerned'){
						var ufs = value.split(",");
						$('#ufs_concerned').val(ufs);
					} else {
						$('#'+input_name).val(value);
					}

					$('.change-sec').switchSection("changeTo",".sec-2");

					
					e.stopPropagation();
					
	
				});
				
		});
		
		

		//build provider select
		$("#providerSelect")
			.xml2html("init", {
				url: 'php/ctrl/SmallQ.php',
				params:'oper=getActiveProviders',
				offSet:1,
				loadOnInit:true
		});

		//build ufs select
		$("#ufs_concerned")
			.xml2html("init", {
				url: 'php/ctrl/UserAndUf.php',
				params:'oper=getUfListing&all=1',
				offSet:1,
				loadOnInit:true
		});

		//build type select
		/*$("#typeSelect")
			.xml2html("init", {
				url: 'php/ctrl/Incidents.php',
				params:'oper=getIncidentTypes',
				loadOnInit:false,
				complete: function(){
					$("#typeSelect option:last").attr("selected",true);
				}
		});*/

		//build commission select
		$("#commissionSelect")
			.xml2html("init", {
				url: 'php/ctrl/SmallQ.php',
				params : 'oper=getCommissions',
				offSet : 1,
				loadOnInit: true
		});


		
		
		//switchTo('overview');
						
			
	});  //close document ready
	</script>
</head>
<body>

	<div id="headwrap">
		<?php include "php/inc/menu.inc.php" ?>
	</div>
	<!-- end of headwrap -->
	
	
	<div class="container">
	
		<div class="row section sec-1">
 			<div class="col-md-1 section sec-1">
		    	<h2><span class="glyphicon glyphicon-chevron-left change-sec" target-section="#sec-1"></span></h2>
		    </div>
		    <div class="col-md-11 section sec-1">
		    	<h1><?=$Text['incident_details'];?>	<span id="incident_id_info">#</span></h1>
		    </div>
		</div>

		<div class="row">
			<div class="col-md-11 section sec-1">
		    	<h1><?=$Text['ti_incidents']; ?></h1>
		    </div>

		    <div class="col-md-11 section sec-3">
		    	<h1><?=$Text['create_incident'];?></h1>
		    </div>

		    <div class="col-md-1">
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
    				Actions <span class="caret"></span>
  				</button>
				<ul class="dropdown-menu" role="menu">
				    <li><a href="#sec-3" class="change-sec"><?php echo $Text['btn_new_incident'];?></a></li>
				    <li class="divider"></li>
				    <li><a href="#"><?=$Text['filter_incidents'];?></a></li>
				    <li><a href="javascript:void(null)" id="today">&nbsp;&nbsp;<?=$Text['filter_todays'];?></a></li>
					<li><a href="javascript:void(null)" id="past2Month">&nbsp;&nbsp;Date range</a></li>
				    <li class="divider"></li>
				    <li><a href="#"><?=$Text['printout'];?></a></li>
				    <li class="divider"></li>
				    <li><a href="#">Export</a></li>
				    <li><a href="javascript:void(null)">&nbsp;&nbsp;pdf</a></li>
					<li><a href="javascript:void(null)">&nbsp;&nbsp;xls</a></li>
				</ul>			
		    </div>
		</div>
		
		<div id="incidents_listing" class="section sec-1">
			<div class="container">
					<table id="tbl_incidents" class="table table-hover">
					<thead>
						<tr>
							<th><input type="checkbox" id="toggleBulkActions" name="toggleBulk"/></th>
							<th><?php echo $Text['id'];?></th>
							<th hideInPrint"><?php echo $Text['subject'];?></th>
							<th><?php echo $Text['priority'];?>&nbsp;&nbsp;</th>
							<th><?php echo $Text['created_by'];?></th>
							<th><?php echo $Text['created'];?></th>
							<th><?php echo $Text['status'];?></th>
							<th class="hidden"><?php echo $Text['incident_type'];?></th>
							<th class="hidden"><?php echo $Text['provider_name'];?></th>
							<th class="hidden"><?php echo $Text['ufs_concerned'];?></th>
							<th class="hidden"><?php echo $Text['comi_concerned'];?></th>
							<th class="hidden hideInPrint"><?=$Text['details'];?></th>
							<th class="maxwidth-100 hideInPrint textAlignRight"><?=$Text['actions'];?></th>
						</tr>
					</thead>
					<tbody>
						<tr class="clickable" incidentId="{id}">
							<td><input type="checkbox" name="bulkAction"/></td>
							<td field_name="incident_id">{id}</td>
							<td field_name="subject" class="hideInPrint"><p class="incidentsSubject">{subject}</p></td>
							<td field_name="priority"><p  class="textAlignCenter">{priority}</p></td>
							<td field_name="operator">{uf_id} {user_name}</td>
							<td field_name="date_posted"><p class="textAlignLeft">{ts}</p></td>
							<td field_name="status" class="textAlignCenter">{status}</td>
							<td field_name="type" class="hidden hideInPrint">{distribution_level}</td>
							<td field_name="type_description" class="hidden">{type_description}</td>
							<td field_name="provider" class="hidden hideInPrint">{provider_concerned}</td>
							<td field_name="provider_name" class="hidden">{provider_name}</td>
							<td field_name="ufs_concerned" class="hidden">{ufs_concerned}</td>
							<td field_name="commission" class="hidden">{commission_concerned}</td>
							<td field_name="incidents_text" class="hidden hideInPrint">{details}</td>
							<td class="hideInPrint">
								<p class="ui-corner-all iconContainer ui-state-default floatRight" title="Delete incident">
									<span class="btn_del_incident ui-icon ui-icon-trash"></span>
								</p>
								<!--  p class="ui-corner-all iconContainer ui-state-default" title="View incident">
									<span class="btn_view_incident ui-icon ui-icon-zoomin"></span>
								</p-->
							</td>
						</tr>
						<tr class="hidden">
							<td class="noBorder"></td>
							<td colspan="11" class="noBorder">{subject}</td>
						</tr>
						<tr class="hidden">
							<td class="noBorder"></td>
							<td colspan="11" class="hidden noBorder">{details}<p>&nbsp;</p></td>
						</tr>
					</tbody>
					
				</table>
			</div>
					
					
			</div>	
		</div>

		<div id="editorWrap" class="section sec-2 sec-3">
			<div class="container">
				<p id="incidentsMsg" class="user_tips"></p>
				<form>
					<input type="hidden" id="incident_id" name="incident_id" value=""/>
					<table class="tblForms">
						<tr>
							<td><label for="subject"><?php echo $Text['subject'];?>:</label></td>
							<td><input type="text" name="subject" id="subject" class="inputTxtLarge inputTxt ui-corner-all" value=""/></td>
							
							
						</tr>
						<tr>
							<td><label for="incidents_text"><?php echo $Text['message'];?>:</label></td>
							<td rowspan="5"><textarea id="incidents_text" name="incidents_text" class="textareaLarge inputTxt ui-corner-all"></textarea></td>
							
							<td><label for="prioritySelect"><?php echo $Text['priority'];?></label></td>
							<td><select id="prioritySelect" name="prioritySelect" class="mediumSelect"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option></select></td>
						</tr>
						<tr>
						
							<td></td>
							<td><label for="statusSelect"><?php echo $Text['status'];?></label></td>
							<td><select id="statusSelect" name="statusSelect" class="mediumSelect"><option value="open"> <?php echo $Text['status_open'];?></option><option value="closed"> <?php echo $Text['status_closed'];?></option></select></td>
						</tr>
						<tr>
							
							<td></td>
							<td><label for="typeSelect"><?=$Text['distribution_level'];?></label></td>
							<td>
								<select id="typeSelect" name="typeSelect" class="mediumSelect">
									<option value="1"><?=$Text['internal_private'];?></option>
									<option value="2"><?=$Text['internal_email_private'];?></option>
									<option value="3"><?=$Text['internal_post'];?></option>
									<option value="4"><?=$Text['internal_email_post'];?></option>
								</select></td>
						</tr>
						<tr>
							
							<td></td>
							<td><label for="ufs_concerned"><?php echo $Text['ufs_concerned']; ?></label></td>
							<td>
								<select id="ufs_concerned" name="ufs_concerned[]" multiple size="6">
								 	<option value="-1" selected="selected"><?php echo $Text['sel_none'];?></option>  
									<option value="{id}"> {id} {name}</option>	
								</select>
							</td>
						</tr>
						<tr>
							
							<td></td>
							<td><label for="providerSelect"><?php echo $Text['provider_concerned'];?></label></td>
							<td>
								<select id="providerSelect" name="providerSelect" class="mediumSelect">
	                    			<option value="-1" selected="selected"><?php echo $Text['sel_none'];?></option>                     
	                    			<option value="{id}"> {name}</option>
								</select>
							</td>
						</tr>
						<tr>
						<td></td>
							<td></td>
							<td><label for="commissionSelect"><?php echo $Text['comi_concerned'];?></label></td>
							<td><select id="commissionSelect" name="commissionSelect" class="mediumSelect">
									<option value="-1" selected="selected"><?php echo $Text['sel_none'];?></option>
									<option value="{description}"> {description}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td></td>
							<td colspan="2" class="textAlignRight">
								<button type="submit" href="#sec-1" data-loading-text="Loading..." class="btn btn-default change-sec"><?php echo $Text['btn_save'];?></button>
								&nbsp;&nbsp;
								<button type="reset" href="#sec-1" class="btn btn-default change-sec"><?php echo $Text['btn_cancel'];?></button>
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>


		
	</div>
	<!-- end of stage wrap -->
</div>
<!-- end of wrap -->

<!-- / END -->
<iframe name="dataFrame" style="display:none"></iframe>

</body>
</html>