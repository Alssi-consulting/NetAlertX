<?php
  //------------------------------------------------------------------------------
  // check if authenticated
  require_once  $_SERVER['DOCUMENT_ROOT'] . '/php/templates/security.php';
?>


<section class="content workflows">
  <div id="workflowContainerWrap" class="bg-grey-dark color-palette  col-sm-12  box-default box-info ">
    <div id="workflowContainer"></div>
    
  </div>
  <div id="buttons" class="buttons col-sm-12">
    <div class="add-workflow col-sm-6">
      <button type="button" class="btn btn-primary btn-default pa-btn bg-green" id="save" onclick="addWorkflow()">
        <?= lang('Gen_Add');?>
      </button>
    </div>
    <div class="save-workflows col-sm-6">
      <button type="button" class="btn btn-primary btn-default pa-btn bg-green" id="save" onclick="saveWorkflows()">
        <?= lang('DevDetail_button_Save');?>
      </button>
    </div>
  </div>
</section>


<script>

let workflows = [];

let fieldOptions = [
        "devMac", "devName", "devOwner", "devType", "devVendor", "devFavorite",
        "devGroup", "devComments", "devFirstConnection", "devLastConnection",
        "devLastIP", "devStaticIP", "devScan", "devLogEvents", "devAlertEvents",
        "devAlertDown", "devSkipRepeated", "devLastNotification", "devPresentLastScan",
        "devIsNew", "devLocation", "devIsArchived", "devParentMAC", "devParentPort",
        "devIcon", "devGUID", "devSite", "devSSID", "devSyncHubNode", "devSourcePlugin"
      ];
      
let triggerTypes = [
  "Devices", "Plugins_Objects"
];

let operatorTypes = [
  "equals", "contains" , "regex"
];

let actionTypes = [
  "update_field", "run_plugin", "delete_device"
];

// --------------------------------------
// Retrieve and process the data
function getData() {
  showSpinner();

  getSetting()

  $.get('php/server/query_json.php?file=workflows.json', function (res) {
    workflows = res;
    console.log(workflows);

    // Store the updated workflows object back into cache
    setCache('workflows', JSON.stringify(workflows));

    renderWorkflows();
    hideSpinner();
  });
}


// --------------------------------------
// Render all workflows
function renderWorkflows() {
  let $container = $("#workflowContainer");
  $container.empty(); // Clear previous UI

  $.each(workflows, function (index, wf) {
    let $wfElement = generateWorkflowUI(wf, index);
    $container.append($wfElement);
  });
}


// --------------------------------------
// Generate UI for a single workflow
function generateWorkflowUI(wf, wfIndex) {

  let $wfContainer = $("<div>", { 
    class: "workflow-card box box-solid box-primary panel panel-default", 
    id: `wf-${wfIndex}-container` 
  });

  // Workflow Name
  let $wfLinkWrap = $("<div>",
    {
      class: " ",
      id: `wf-${wfIndex}-header` 
    }
  )

  let $wfHeaderLink = $("<a>",
    {
      "class": "",
      "data-toggle": "collapse",
      "data-parent": "#workflowContainer",
      "aria-expanded": false,
      "href" : `#wf-${wfIndex}-collapsible-panel`
    }
  )

  let $wfHeaderHeading = $("<h4>",
    {
      class: "panel-title"    
    }
  ).text(wf.name)

  $wfContainer.append($wfHeaderLink.append($wfLinkWrap.append($wfHeaderHeading)));

  // Collapsible panel start

  // Get saved state from localStorage
  let panelState = localStorage.getItem(`wf-${wfIndex}-collapsible-panel`);
  let isOpen = panelState === "true"; // Convert stored string to boolean

  console.log(`panel isOpen: ${isOpen}` );
  

  let $wfCollapsiblePanel = $("<div>", { 
    class: `panel-collapse collapse  ${isOpen ? 'in' : ''}`, 
    id: `wf-${wfIndex}-collapsible-panel` 
  });

  let $wfNameInput = createEditableInput(
    `[${wfIndex}].name`, 
    "Workflow name", 
    wf.name, 
    `wf-${wfIndex}-name`, 
    "workflow-name-input"
  );

  $wfCollapsiblePanel.append($wfNameInput)

  // Trigger Section with dropdowns
  let $triggerSection = $("<div>",
    {
      class: "condition-list box  box-secondary"
    }
  ).append("<strong>Trigger:</strong> ");

  let $triggerTypeDropdown = createEditableDropdown(
    `[${wfIndex}].trigger.object_type`, 
    "Trigger Type", 
    triggerTypes, 
    wf.trigger.object_type, 
    `wf-${wfIndex}-trigger-object-type`
  );

  let $eventTypeDropdown = createEditableDropdown(
    `[${wfIndex}].trigger.event_type`, 
    "Event Type", 
    ["update", "create", "delete"], 
    wf.trigger.event_type, 
    `wf-${wfIndex}-trigger-event-type`
  );

  $triggerSection.append($triggerTypeDropdown);
  $triggerSection.append($eventTypeDropdown);
  $wfCollapsiblePanel.append($triggerSection);

  // Conditions
  let $conditionsContainer = $("<div>").append("<strong>Conditions:</strong>");
  $conditionsContainer.append(renderConditions(wfIndex, `[${wfIndex}]`, 0, wf.conditions));
  
  $wfCollapsiblePanel.append($conditionsContainer);


  // Actions with action.field as dropdown
  let $actionsContainer = $("<div>",
    {
      class: "actions-list box  box-secondary"
    }
  ).append("<strong>Actions:</strong>");

  lastActionIndex = 0
  $.each(wf.actions, function (actionIndex, action) {
    let $actionEl = $("<div>");

    // Dropdown for action.field
    let $fieldDropdown = createEditableDropdown(
      `[${wfIndex}].actions[${actionIndex}].field`, 
      "Field", 
      fieldOptions, 
      action.field, 
      `wf-${wfIndex}-actionIndex-${actionIndex}-field`
    );


    // Dropdown for action.type
    let $actionDropdown= createEditableDropdown(
      `[${wfIndex}].actions[${actionIndex}].type`, 
      "Type", 
      actionTypes, 
      action.field, 
      `wf-${wfIndex}-actionIndex-${actionIndex}-type`
    );


    // Action Value Input (Editable)
    let $actionValueInput = createEditableInput(
      `[${wfIndex}].actions[${actionIndex}].value`, 
      "Value", 
      action.value, 
      `wf-${wfIndex}-actionIndex-${actionIndex}-value`, 
      "action-value-input"
    );

    $actionEl.append($actionDropdown);
    $actionEl.append($fieldDropdown);
    $actionEl.append($actionValueInput);

    $actionsContainer.append($actionEl);

    lastActionIndex = actionIndex
  });

  // add actions group button
  let $actionAddButton = $("<button>", {
      class : "btn btn-secondary add-action",
      lastActionIndex : lastActionIndex,
      wfIndex: wfIndex
    }).text("Add Action")

  $actionsContainer.append($actionAddButton)
  $wfCollapsiblePanel.append($actionsContainer);

  $wfContainer.append($wfCollapsiblePanel)

  return $wfContainer;
}


// --------------------------------------
// Render conditions recursively
function renderConditions(wfIndex, parentIndexPath, conditionGroupsIndex, conditions) {
  let $conditionList = $("<div>", { 
    class: "condition-list  ",
    parentIndexPath: parentIndexPath 
  });

  lastConditionIndex = 0

  $.each(conditions, function (conditionIndex, condition) {
    
    let currentPath = `${parentIndexPath}.conditions[${conditionIndex}]`;

    if (condition.logic) {
      let $nestedCondition = $("<div>",
        {
          class : "condition box  box-secondary"
        }
      );

      let $logicDropdown = createEditableDropdown(
        `${currentPath}.logic`, 
        "Logic Rules", 
        ["AND", "OR"], 
        condition.logic, 
        `wf-${wfIndex}-${currentPath.replace(/\./g, "-")}-logic` // id
      );

      $nestedCondition.append($logicDropdown);
      
      $conditionListNested = renderConditions(wfIndex, currentPath, conditionGroupsIndex+1, condition.conditions) 

      $nestedCondition.append($conditionListNested); // Recursive call for nested conditions

      $conditionList.append($nestedCondition);

    } else {
      // INDIVIDUAL CONDITIONS
      let $conditionItem = $("<div>",
      {
        class: "panel",
        conditionIndex: conditionIndex,
        wfIndex: wfIndex
      });

      // Create dropdown for condition field
      let $fieldDropdown = createEditableDropdown(
        `${currentPath}.field`,"Field", 
        fieldOptions, 
        condition.field, 
        `wf-${wfIndex}-${currentPath.replace(/\./g, "-")}-field`
      );

      // Create dropdown for operator
      let $operatorDropdown = createEditableDropdown(
        `${currentPath}.operator`, 
        "Operator", 
        operatorTypes, 
        condition.operator, 
        `wf-${wfIndex}-${currentPath.replace(/\./g, "-")}-operator`
      );

      // Editable input for condition value
      let $editableInput = createEditableInput(
          `${currentPath}.value`, 
          "Condition Value", 
          condition.value, 
          `wf-${wfIndex}-${currentPath.replace(/\./g, "-")}-value`, 
          "condition-value-input"
       );

      $conditionItem.append($fieldDropdown); // Append field dropdown
      $conditionItem.append($operatorDropdown); // Append operator dropdown
      $conditionItem.append($editableInput); // Append editable input for condition value

      let $conditionRemoveButton = $("<button>", {
        class : "btn btn-secondary remove-condition",
        lastConditionIndex : lastConditionIndex,
        wfIndex: wfIndex,
        parentIndexPath: parentIndexPath
      }).text("Remove Condition")

      $conditionItem.append($conditionRemoveButton);

      $conditionList.append($conditionItem);      
    }

    lastConditionIndex = conditionIndex
  });

  
  if(conditionGroupsIndex != 0){
    // add conditions group button
    let $conditionAddButton = $("<button>", {
      class : "btn btn-secondary add-condition",
      lastConditionIndex : lastConditionIndex,
      wfIndex: wfIndex,
      parentIndexPath: parentIndexPath
    }).text("Add Condition")

    // add conditions group button
    let $conditionGroupRemoveButton = $("<button>", {
      class : "btn btn-secondary remove-condition-group",
      lastConditionIndex : lastConditionIndex,
      wfIndex: wfIndex,
      parentIndexPath: parentIndexPath
    }).text("Remove Condition Group")

    $conditionList.append($conditionAddButton);
    $conditionList.append($conditionGroupRemoveButton);
  }


  // add conditions group button
  let $conditionsGroupAddButton = $("<button>", {
    class : "btn btn-secondary add-condition-group",
    wfIndex: wfIndex,
    parentIndexPath: parentIndexPath
  }).text("Add Condition Group")
  
  
  $conditionList.append($conditionsGroupAddButton);
  
  return $conditionList;
}


// --------------------------------------
// Render SELECT Dropdown with Predefined Values
function createEditableDropdown(jsonPath, labelText, options, selectedValue, id) {

  let $wrapper = $("<div>", {
    class: "form-group col-xs-12"
  });

  let $label = $("<label>", {   
    for: id, 
    class: "col-sm-4 col-xs-12 control-label "
  }).text(labelText);

  // Create select wrapper
  let $selectWrapper = $("<div>", {
    class: "col-sm-8 col-xs-12"
  });

  // Create select element
  let $select = $("<select>", {
    id: id,
    jsonPath: jsonPath,
    class: "form-control col-sm-8 col-xs-12"
  });

  // Add options to the select dropdown
  $.each(options, function (_, option) {
    let $option = $("<option>", { value: option }).text(option);
    if (option === selectedValue) {
      $option.attr("selected", "selected"); // Set the default selection
    }
    $select.append($option);
  });

  // Trigger onSave when the selection changes
  $select.on("change", function() {
    let newValue = $select.val();
    console.log(`Selected new value: ${newValue}`);
    console.log(`Selected new jsonPath: ${$select.attr("jsonPath")}`);
    
    updateWorkflowObject(newValue, $select.attr("jsonPath")); // Call the onSave callback with the new value

  });

  $wrapper.append($label);
  $wrapper.append($selectWrapper.append($select));
  return $wrapper;
}

// --------------------------------------
// Render INPUT HTML element
function createEditableInput(jsonPath, labelText, value, id, className = "") {

  // prepare wrapper
  $wrapper = $("<div>", {
    class: "form-group col-xs-12"
  });

  let $label = $("<label>", {   
    for: id, 
    class: "col-sm-4 col-xs-12 control-label "
  }).text(labelText);

  // Create input wrapper
  let $inputWrapper = $("<div>", {
    class: "col-sm-8 col-xs-12"
  });

  // console.log(jsonPath);
  
  let $input = $("<input>", {
    type: "text",
    jsonPath: jsonPath,
    id: id,
    value: value,
    class: className + " col-sm-8 col-xs-12 form-control "
  });

  // Optional: Add a change event listener to update the workflow name
  $input.on("change", function () {
    let newValue = $input.val();
    console.log(`Value changed to: ${newValue}`);
  });

  // Trigger onSave when the user presses Enter or the input loses focus
  $input.on("blur keyup", function (e) {
    if (e.type === "blur" || e.key === "Enter") {      
      let newValue = $input.val();
      console.log(`Selected new value: ${newValue}`);

      updateWorkflowObject(newValue, $input.attr("jsonPath")); // Call the onSave callback with the new value      
    }
  });

  $wrapper.append($label)
  $wrapper.append($inputWrapper.append($input))
  return $wrapper;
}

// --------------------------------------
// Updating the in-memory workflow object
function updateWorkflowObject(newValue, jsonPath) {
  // Load workflows from cache if available
  let workflows = JSON.parse(getCache('workflows')) || []; // Initialize as an array

  console.log("Initial workflows:", workflows);

  workflows = updateJsonByPath(workflows, jsonPath, newValue)

  console.log("Updated workflows:", workflows);

  // Store the updated workflows object back into cache
  setCache('workflows', JSON.stringify(workflows)); // Store as a string in localStorage
}


/**
 * Updates the given JSON structure at the location specified by a JSON path.
 * @param {object|array} json - The JSON object or array to update.
 * @param {string} path - The JSON path string, e.g. "[1].conditions[0].conditions[2].conditions[0].field".
 * @param {*} newValue - The new value to set at the given path.
 * @returns {object|array} - The updated JSON.
 */
function updateJsonByPath(json, path, newValue) {
  const tokens = parsePath(path);
  recursiveUpdate(json, tokens, newValue);
  return json;
}

/**
 * Recursively traverses the JSON structure to update the property defined by tokens.
 * @param {object|array} current - The current JSON object or array.
 * @param {Array<string|number>} tokens - An array of tokens representing the path.
 * @param {*} newValue - The value to set at the target location.
 */
function recursiveUpdate(current, tokens, newValue) {
  // When only one token is left, update that property/element with newValue.
  if (tokens.length === 1) {
    const key = tokens[0];
    current[key] = newValue;
    return;
  }
  
  const token = tokens[0];
  
  // If the next level does not exist, optionally create it.
  if (current[token] === undefined) {
    // Determine if the next token is an array index or a property.
    current[token] = typeof tokens[1] === 'number' ? [] : {};
  }
  
  // Recursively update the next level.
  recursiveUpdate(current[token], tokens.slice(1), newValue);
}

/**
 * Parses a JSON path string into an array of tokens.
 * For example, "[1].conditions[0].conditions[2].conditions[0].field" becomes:
 * [1, "conditions", 0, "conditions", 2, "conditions", 0, "field"]
 * @param {string} path - The JSON path string.
 * @returns {Array<string|number>} - An array of tokens.
 */
function parsePath(path) {
  const tokens = [];
  const regex = /(\w+)|\[(\d+)\]/g;
  let match;
  while ((match = regex.exec(path)) !== null) {
    if (match[1]) {
      tokens.push(match[1]);
    } else if (match[2]) {
      tokens.push(Number(match[2]));
    }
  }
  return tokens;
}



// ---------------------------------------------------
// Buttons functionality
// ---------------------------------------------------

// ---------------------------------------------------
// Function to add a new condition
function addCondition(wfIndex, parentIndexPath) {
    if (!parentIndexPath) return;

    // Navigate to the target nested object
    let target = getNestedObject(workflows, parentIndexPath);

    console.log("Target:", target);

    if (!target || !target.conditions) {
        console.error("❌ Invalid path or conditions array missing:", parentIndexPath);
        return;
    }

    // Add a new condition to the conditions array
    target.conditions.push({
        field: fieldOptions[0], // First option from field dropdown
        operator: operatorTypes[0], // First operator
        value: "" // Default empty value
    });

    // 🔥 Ensure the workflows object is updated in memory
    workflows[wfIndex] = { ...workflows[wfIndex] };

    // 🔥 Update the cache with the modified workflows object
    setCache("workflows", JSON.stringify(workflows));

    // Re-render the UI
    renderWorkflows();
}

// ---------------------------------------------------
// Function to add a new condition group
function addConditionGroup(wfIndex, parentIndexPath) {
    if (!parentIndexPath) return;

    // Navigate to the target nested object
    let target = getNestedObject(workflows, parentIndexPath);

    console.log("Target:", target);

    if (!target || !target.conditions) {
        console.error("❌ Invalid path or conditions array missing:", parentIndexPath);
        return;
    }

    // Add a new condition group to the conditions array
    target.conditions.push({
        logic: "AND",
        conditions: []
    });

    // 🔥 Ensure the workflows object is updated in memory
    workflows[wfIndex] = { ...workflows[wfIndex] };

    // 🔥 Update the cache with the modified workflows object
    setCache("workflows", JSON.stringify(workflows));

    // Re-render the UI
    renderWorkflows();
}

// ---------------------------------------------------
// Function to add a new action
function addAction(wfIndex) {
    let newAction = {
        type: actionTypes[0],
        field: fieldOptions[0],
        value: ""
    };
    workflows[wfIndex].actions.push(newAction);
    renderWorkflows();
}

function removeCondition(wfIndex, parentIndexPath, lastConditionIndex) {
    if (!parentIndexPath || lastConditionIndex === undefined) return;

    // Navigate to the target nested object
    let target = getNestedObject(workflows, parentIndexPath);

    console.log("Target before removal:", target);

    if (!target || !Array.isArray(target.conditions)) {
        console.error("❌ Invalid path or conditions array missing:", parentIndexPath);
        return;
    }

    // Remove the specified condition
    target.conditions.splice(lastConditionIndex, 1);

    // 🔥 Ensure the workflows object is updated in memory
    workflows[wfIndex] = { ...workflows[wfIndex] };

    // 🔥 Update the cache with the modified workflows object
    setCache("workflows", JSON.stringify(workflows));

    // Re-render the UI
    renderWorkflows();
}

function removeConditionGroup(wfIndex, parentIndexPath) {
    if (!parentIndexPath) return;

    // Split the path by dots
    const parts = parentIndexPath.split('.');
    
    // Extract the last part (index)
    const lastIndex = parts.pop().replace(/\D/g, '');  // Remove any non-numeric characters
    
    // Rebuild the path without the last part
    const newPath = parts.join('.');

    console.log(parentIndexPath);    
    console.log(newPath);    

    // Navigate to the target nested object
    let target = getNestedObject(workflows, newPath);

    console.log("Target before removal:", target);

    if (!target || !Array.isArray(target.conditions)) {
        console.error("❌ Invalid path or conditions array missing:", parentIndexPath);
        return;
    }

    // Remove the specified condition group
    delete target.conditions.splice(lastIndex, 1);

    // 🔥 Ensure the workflows object is updated in memory
    workflows[wfIndex] = { ...workflows[wfIndex] };

    // 🔥 Update the cache with the modified workflows object
    setCache("workflows", JSON.stringify(workflows));

    // Re-render the UI
    renderWorkflows();
}


// ---------------------------------------------------
// Helper function to navigate to a nested object using the provided path
function getNestedObject(obj, path) {
    // console.log("🔍 Getting nested object:", { obj, path });

    // Convert bracket notation to dot notation (e.g., '[1].conditions[0]' → '1.conditions.0')
    const keys = path.replace(/\[(\d+)\]/g, '.$1').split('.').filter(Boolean);

    return keys.reduce((o, key, index) => {
        if (o && o[key] !== undefined) {
            // console.log(`✅ Found: ${keys.slice(0, index + 1).join('.')} ->`, o[key]);
            return o[key];
        } else {
            console.warn(`❌ Failed at: ${keys.slice(0, index + 1).join('.')}`);
            console.warn("🔍 Tried getting nested object:", { obj, path });
            return null;
        }
    }, obj);
}

// ---------------------------------------------------
// Event listeners
$(document).on("click", ".add-condition", function () {
    let wfIndex = $(this).attr("wfindex");
    let parentIndexPath = $(this).attr("parentIndexPath");
    addCondition(wfIndex, parentIndexPath);
});

$(document).on("click", ".add-condition-group", function () {
    let wfIndex = $(this).attr("wfindex");
    let parentIndexPath = $(this).attr("parentIndexPath");
    addConditionGroup(wfIndex, parentIndexPath);
});

$(document).on("click", ".add-action", function () {
    let wfIndex = $(this).attr("wfIndex");
    addAction(wfIndex);
});

// Event Listeners for Removing Conditions
$(document).on("click", ".remove-condition", function () {
    let wfIndex = $(this).attr("wfindex");
    let parentIndexPath = $(this).attr("parentIndexPath");
    let lastConditionIndex = parseInt($(this).attr("lastConditionIndex"), 10);
    
    removeCondition(wfIndex, parentIndexPath, lastConditionIndex);
});

// Event Listeners for Removing Condition Groups
$(document).on("click", ".remove-condition-group", function () {
    let wfIndex = $(this).attr("wfindex");
    let parentIndexPath = $(this).attr("parentIndexPath");
    let lastConditionIndex = parseInt($(this).attr("lastConditionIndex"), 10);
    
    removeConditionGroup(wfIndex, parentIndexPath);
});

// ---------------------------------------------------
// Handling open/closed state of collapsible panels
$(document).ready(function() {
    $(".panel-collapse").each(function() {
        let panelId = $(this).attr("id");
        let isOpen = localStorage.getItem(panelId) === "true";

        if (isOpen) {
            $(this).addClass("in");
        }
    });

    $(document).on("shown.bs.collapse", ".panel-collapse", function() {
    localStorage.setItem($(this).attr("id"), "true");
        console.log("Panel opened:", $(this).attr("id"));
    });

    $(document).on("hidden.bs.collapse", ".panel-collapse", function() {
        localStorage.setItem($(this).attr("id"), "false");
        console.log("Panel closed:", $(this).attr("id"));
    });
});

// --------------------------------------
// Initialize
$(document).ready(function () {
  getData();
});


</script>