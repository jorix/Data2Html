<!DOCTYPE html>
<html lang="ca">
<head>
	<meta charset="UTF-8">
	<title>form-bootstrap</title>
   
    <script src="../../external/js/jquery-2.1.0/jquery.js" ></script>
    <link  href="../../external/js/bootstrap-3.3.6-dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="../../external/js/bootstrap-3.3.6-dist/js/bootstrap.min.js" ></script>

</head>
<body>

	<div id="headwrap">
		<!-- include "../../php/inc/menu.inc.php" -->
	</div>
	<!-- end of main menu / headwrap -->

	<!-- sub nav -->
	<div class="container section sec-1">
		<div class="row">
			<nav class="navbar navbar-default" role="navigation" id="ax-submenu">
			  	<div class="navbar-header">
			     	<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#sub-navbar-collapse">
				        <span class="sr-only">Toggle navigation</span>
				        <span class="icon-bar"></span>
				        <span class="icon-bar"></span>
				        <span class="icon-bar"></span>
			      	</button>
	    		</div>

	    		<div class="navbar-collapse collapse" id="sub-navbar-collapse">
					<div class="col-md-4">
						<button type="button" class="btn btn-success btn-sm navbar-btn section sec-1" id="btn-create-incident">
		    				<span class="glyphicon glyphicon glyphicon-ok-sign"></span> New incident
		  				</button>
	  				</div>

	  				<div class="col-md-3 section sec-3 sec-1">
						<form class="navbar-form pull-right" role="date">
							<div class="form-group">
		                        <div class='input-group date input-group-sm' id='datepicker-from' >
		                            <input type='text' class="form-control" id="date-from" data-format="dddd, ll" placeholder="From" />
		                            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
		                            </span>
		                        </div>
		                    </div>
		                </form>
		            </div>

		            <div class="col-md-3">
						<form class="navbar-form" role="date">
							<div class="form-group">
		                        <div class='input-group date input-group-sm' id='datepicker-to' >
		                            <input type='text' class="form-control" name="date-to" data-format="dddd, ll" placeholder="To" />
		                            <span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span>
		                            </span>
		                        </div>
		                    </div>
		                </form>
	            	</div>

					

					<div class="btn-group col-md-1">
						<button type="button" class="btn btn-default btn-sm navbar-btn dropdown-toggle" data-toggle="dropdown">
		    				Actions <span class="caret"></span>
		  				</button>
						<ul class="dropdown-menu" role="menu">
							<li><a href="javascript:void(null)" class="ctx-nav ctx-nav-export-bill"><span class="glyphicon glyphicon-export"></span> btn_export</a></li>
						</ul>
						
					</div>



	  				<div class="btn-group col-md-1 pull-right">
						<button type="button" class="btn btn-default btn-sm navbar-btn dropdown-toggle" data-toggle="dropdown">
							<span class="glyphicon glyphicon-filter"></span>&nbsp; <span class="caret"></span>
						</button>
						<ul class="dropdown-menu" role="menu">
							<li><a href="javascript:void(null)">Filter</a></li>
						    <li class="level-1-indent"><a href="javascript:void(null)" data="days,0" class="ctx-nav-filter">Today</a></li>
							<li class="level-1-indent"><a href="javascript:void(null)" data="weeks,1" class="ctx-nav-filter">Last week</a></li>
							<li class="level-1-indent"><a href="javascript:void(null)" data="months,1" class="ctx-nav-filter">Last month</a></li>
							<li class="level-1-indent"><a href="javascript:void(null)" data="months,3" class="ctx-nav-filter">Last 3 month</a></li>
							<li class="level-1-indent"><a href="javascript:void(null)" data="months,12" class="ctx-nav-filter">Last year</a></li>

						</ul>
					</div>
					


		      	</div>
			</nav>
		</div>
	</div><!-- end sub nav -->

	
	<div class="container" id="aix-title">
		<div class="row">
		    <div class="col-md-10 section sec-2">
		    	<h1><span class="glyphicon glyphicon-chevron-left change-sec" target-section="#sec-1"></span> incident_details	<span id="incident_id_info">#</span></h1>
		    </div>
			
			<div class="col-md-10 section sec-1">
		    	<h1>ti_incidents</h1>
		    </div>

		    <div class="col-md-10 section sec-3">
		    	<h1>create_incident</h1>
		    </div>
		</div>
	</div>


	<div class="container">
		<div class="row">
			<div id="incidents_listing" class="section sec-1">
				<table id="tbl_incidents" class="table table-hover">
					<thead>
						<tr>
							<th><input type="checkbox" id="toggleBulkActions" name="toggleBulk"/></th>
							<th>id</th>
							<th>subject</th>
							<th>priority&nbsp;&nbsp;</th>
							<th>created_by</th>
							<th>created</th>
							<th>status</th>
							<th class="hidden">incident_type</th>
							<th class="hidden">provider_name</th>
							<th class="hidden">ufs_concerned</th>
							<th class="hidden">comi_concerned</th>
							<th class="hidden">details</th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<tr class="clickable" incidentId="{id}">
							<td><input type="checkbox" name="bulkAction"/></td>
							<td field_name="incident_id">{id}</td>
							<td field_name="subject"><p class="incidentsSubject">{subject}</p></td>
							<td field_name="priority"><p  class="textAlignCenter">{priority}</p></td>
							<td field_name="operator">{uf_id} {user_name}</td>
							<td field_name="date_posted"><p class="textAlignLeft">{ts}</p></td>
							<td field_name="status" class="textAlignCenter">{status}</td>
							<td field_name="type" class="hidden">{distribution_level}</td>
							<td field_name="type_description" class="hidden">{type_description}</td>
							<td field_name="provider" class="hidden">{provider_concerned}</td>
							<td field_name="provider_name" class="hidden">{provider_name}</td>
							<td field_name="ufs_concerned" class="hidden">{ufs_concerned}</td>
							<td field_name="commission" class="hidden">{commission_concerned}</td>
							<td field_name="incidents_text" class="hidden">{details}</td>
							<td>
								<span class="glyphicon glyphicon-remove-circle del-incident"></span>
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

	<hr>
    <h1>editor</h1>
	<div class="section sec-2 sec-3">
		<div class="container">
            <div class="form-horizontal" >
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <div style="width:60em; background-color:#faa;">60em</div>
                        <div style="width:35em; background-color:#faa;">35em</div>
                        <span>0123456789</span>
                        <span style="color:#888">0123456789</span>
                        <span>0123456789</span>
                        <span style="color:#888">0123456789</span>
                        <span>0123456789</span>
                        <span style="color:#888">0123456789</span>
                        <span style="color:green;">(60)</span><br>
                        
                        <span>xxxxxxxxxx</span>
                        <span style="color:#888">xxxxxxxxxx</span>
                        <span>xxxxxxxxxx</span>
                        <span style="color:#888">xxxxxxxxxx</span>
                        <span>xxxxxxxxxx</span>
                        <span style="color:#888">xxxxxxxxxx</span>
                        <span style="color:green;">(60)</span><br>
                        
                        <div style="width:32em; background-color:#faa;">32em</div>
                        <span>abcde.fghi</span>
                        <span style="color:#888">j-kl mnopq</span>
                        <span>rs,uvx(y)z</span>
                        <span style="color:#888">ABCDE.FGHI</span>
                        <span>J-KL MNOPQ</span>
                        <span style="color:#888">RS,UVX(Y)Z</span>
                        <span style="color:green;">(60)</span><br>
                        
                        <div style="width:30em; background-color:#faa;">30em</div>
                        <span>abcde.fghi</span>
                        <span style="color:#888">j-kl mnopq</span>
                        <span>rs,uvx(y)z</span>
                        <span style="color:#888">abcde.fghi</span>
                        <span>j-kl mnopq</span>
                        <span style="color:#888">RS,UVX(Y)Z</span>
                        <span style="color:green;">(60)</span><br>
                    </div>
                    <br>
                    <div class="col-sm-offset-2 col-sm-5">
                        <div style="background-color:#afa;">sm-5</div>
                        text: 60/5 = 12-characters per sm - 10-char_padding
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-4" style="background-color:#aaf;">
                        <div style="background-color:#afa;">sm-4</div>
                        text: 60/5 = 15-digits per sm - 7-digit_padding
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">$${title}</label>
                    <div class="col-sm-1">
                        <input type="text" value="Abcd|"
                            class="form-control">
                    </div>
                    <div class="col-sm-1">
                        <input type="text" value="12345|"
                            class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">$${title}</label>
                    <div class="col-sm-2">
                        <input type="text" value="Abcde.fghi,uvx(y)|"
                            class="form-control">
                    </div>
                    <div class="col-sm-2">
                        <input type="text" value="1234567801234567|"
                            class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">$${title}</label>
                    <div class="col-sm-10">
                        <input type="text" 
                            class="form-control"
                            placeholder="$${description}">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox"> Remember me
                            </label>
                        </div>
                    </div>
                </div>
        
                <div class="form-group">
                    <label class="col-sm-2 control-label">$${title}</label>
                    <div class="col-sm-6">
                        <input type="text" 
                            class="form-control"
                            placeholder="$${description}">
                    </div>
                    <div class="col-sm-2">
                        <div class="checkbox">
                            <label class="control-label">
                                <input type="checkbox"> Remember mex
                            </label>
                        </div>
                    </div>
                </div>
        
				<div class="form-group">
					<label class="col-sm-2 control-label">subject</label>
					<div class="col-sm-4">
						<input type="text" class="form-control" placeholder="subject" value="">
					</div>
                    <label class="col-sm-2 control-label">subject</label>
					<div class="col-sm-4">
						<input type="text" class="form-control " placeholder="subject" value="">
					</div>
                </div>
                
                <div class="form-group">
					<label class="col-sm-2 control-label">subject</label>
					<div class="col-sm-10">
						<input type="text" class="form-control" placeholder="subject" value="">
					</div>
                </div>
                
				<div class="form-group">
					<label for="details" class="col-sm-2 control-label">message</label>
					<div class="col-sm-6">
						<textarea id="incidents_text" name="details" class="form-control" placeholder="Your message here"></textarea>
					</div>
				</div>

				<div class="form-group">
					<label for="priority" class="col-sm-2 control-label">priority</label>
					<div class="col-sm-2">
						<select id="prioritySelect" name="priority" class="form-control"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option></select>
					</div>
				</div>
	
				<div class="form-group">
						<label for="status" class="col-sm-2 control-label">status</label>
					<div class="col-sm-2">
						<select id="statusSelect" name="status" class="form-control"><option value="open"> status_open</option><option value="closed"> status_closed</option></select>
					</div>
					</div>

				<div class="form-group">
						<label for="priority" class="col-sm-2 control-label">distribution_level</label>
					<div class="col-sm-4">
						<select id="typeSelect" name="incident_type_id" class="form-control">
								<option value="1">internal_private</option>
								<option value="2">internal_email_private</option>
								<option value="3">internal_post</option>
								<option value="4">internal_email_post</option>
						</select>
					</div>
					</div>

					<div class="form-group">
						<label for="ufs_concerned" class="col-sm-2 control-label">ufs_concerned</label>
					<div class="col-sm-4">
						<select id="ufs_concerned" name="ufs_concerned[]" multiple class="form-control" size="6">
							 	<option value="-1" selected="selected">sel_none</option>  
								<option value="{id}"> {id} {name}</option>	
						</select>
					</div>
					</div>

				<div class="form-group">
						<label for="provider_concerned" class="col-sm-2 control-label">provider_concerned</label>
					<div class="col-sm-4">
						<select id="providerSelect" name="provider_concerned" class="form-control">
                    			<option value="-1" selected="selected">sel_none</option>                     
                    			<option value="{id}"> {name}</option>
						</select>
					</div>
					</div>

				<div class="form-group">
						<label for="commission_concerned" class="col-sm-2 control-label">comi_concerned</label>
					<div class="col-sm-4">
						<select id="commissionSelect" name="commission_concerned" class="form-control">
								<option value="-1" selected="selected">sel_none</option>
								<option value="{description}"> {description}</option>
						</select>
					</div>
					</div>
					<div>&nbsp;</div>

					<div class="form-group">
					<div class="col-sm-5"></div>
						<div class="cols-sm-1">
						<button type="reset" class="btn btn-default change-sec" target-section="#sec-1">btn_cancel</button>
						&nbsp;&nbsp;
						<button type="submit" id="save-btn" class="btn btn-primary ladda-button" data-style="slide-left" ><span class="ladda-label">btn_save</span></button>

					</div>

				</div>
			</div><!-- form-horizontal -->
		</div>
	</div>

</body>
</html>