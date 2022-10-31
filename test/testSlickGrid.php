<!DOCTYPE html>
<html lang="en">
<head>
  <base href="http://localhost:8888/cct6/">
  <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>SlickGrid example 12: Fill browser</title>
  <link rel="stylesheet" href="SlickGrid/slick.grid.css" type="text/css"/>
  <link rel="stylesheet" href="SlickGrid/examples/examples.css" type="text/css"/>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      background-color: White;
      overflow: auto;
    }

    body {
      font: 11px Helvetica, Arial, sans-serif;
    }

    #container {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
    }

    #description {
      position: fixed;
      top: 30px;
      right: 30px;
      width: 25em;
      background: beige;
      border: solid 1px gray;
      z-index: 1000;
    }

    #description h2 {
      padding-left: 0.5em;
    }
  </style>
</head>
<body>
<div id="container"></div>
</div>

<script src="SlickGrid/lib/jquery-1.7.min.js"></script>
<script src="SlickGrid/lib/jquery.event.drag-2.2.js"></script>

<script src="SlickGrid/slick.core.js"></script>
<script src="SlickGrid/slick.grid.js"></script>
<script>
var grid;
var data = [];
columns = [
	{ id: "issue_no",            name: "Issue No.",    field: "issue_no", width: 60 },
	{ id: "issue_status",        name: "Issue Status", field: "issue_status", width: 60  },
	{ id: "ticket_no",           name: "Ticket No.",   field: "ticket_no", width: 60  },
	{ id: "ticket_status",       name: "Status",       field: "ticket_status", width: 60  },
	{ id: "ticket_state",        name: "State",        field: "ticket_state", width: 60  },
	{ id: "ticket_open_start",   name: "Open/Start",   field: "ticket_open_start", width: 60  },
	{ id: "ticket_closed_end",   name: "Closed/End",   field: "ticket_closed_end", width: 60  },
	{ id: "ticket_closure_code", name: "Closure Code", field: "ticket_closure_code", width: 60  },
	{ id: "ticket_type",         name: "Type",         field: "ticket_type", width: 60  },
	{ id: "ticket_category",     name: "Category",     field: "ticket_category", width: 60  },
	{ id: "ticket_component",    name: "Component",    field: "ticket_component", width: 60  },
	{ id: "ticket_outage",       name: "Out?",         field: "ticket_outage", width: 60  },
	{ id: "ticket_change",       name: "Chg?",         field: "ticket_change", width: 60  },
	{ id: "ticket_escalated",    name: "Esc?",         field: "ticket_escalated", width: 60  },
	{ id: "ticket_severity",     name: "Sev?",         field: "ticket_severity", width: 60  },
	{ id: "ticket_risk",         name: "Risk",         field: "ticket_risk", width: 60  },
	{ id: "ticket_emergency",    name: "Emer?",        field: "ticket_emergency", width: 60  },
	{ id: "ticket_summary",      name: "Summary",      field: "ticket_summary", width: 60  },
	{ id: "ticket_assign_to",    name: "Assigned To",  field: "ticket_assign_to", width: 60  }
];
var options = {
	enableCellNavigation:         false,
	enableColumnReorder:          false
};

  	

  grid = new Slick.Grid("#container", data, columns, options);
</script>
</body>
</html>