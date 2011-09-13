<div id="results_error" class="backadel_error">
  <br />
</div>

<form id="query" action='results.php' method='post'>
    <div id='form_container'>
        <div id='anyall_row'>
            {"match"|s} <select name='type'>
                <option>ALL</option>
                <option>ANY</option>
            </select> {"of_these_constraints"|s}:
            <img src='images/delete.png' class='delete_constraint' alt=''/>
            <img src='images/add.png' class='add_constraint' alt=''/>
        </div>
        <div id='group_constraints'>
            <div class='constraint' id='c0_constraint'>
                <select name='c0_criteria'>
                    <option>Shortname</option>
                    <option>Fullname</option>
                    <option>Course Id #</option>
                    <option>Category</option>
                </select>
                <select name='c0_operator'>
                    <option>is</option>
                    <option>is not</option>
                    <option>contains</option>
                    <option>does not contain</option>
                </select>
                <span id='c0_search_term_0'>
                    <input name='c0_search_term_0' type='text'/>
                    <img src='images/add.png' class='add_search_term' alt=''/>
                    <input id='c0_st_num' value='1' type='hidden'/>
                </span>
            </div>
        </div>
        <div id='button'>
            <input type='submit' value='{"build_search_button"|s}' />
        </div>
    </div>
</form>

<div id="backup_error" class="backadel_error">
    <br />
</div>
