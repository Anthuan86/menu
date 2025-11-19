<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for local_points (English).
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'Points System';
$string['points'] = 'Points';
$string['point'] = 'Point';
$string['mypoints'] = 'My Points';
$string['coursepoints'] = 'Course Points';
$string['globalpoints'] = 'Global Points';
$string['totalpoints'] = 'Total Points';
$string['yourpoints'] = 'Your Points';

// Capabilities.
$string['points:viewown'] = 'View own points';
$string['points:viewall'] = 'View all users\' points';
$string['points:award'] = 'Award points to users';
$string['points:managerules'] = 'Manage point rules';
$string['points:configure'] = 'Configure points plugin';
$string['points:viewcourse'] = 'View course points';
$string['points:awardincourse'] = 'Award points in course';
$string['points:managerulesincourse'] = 'Manage rules in course';

// Settings.
$string['enabled'] = 'Enable Points System';
$string['enabled_desc'] = 'Enable or disable the entire points system';
$string['showinnav'] = 'Show in navigation';
$string['showinnav_desc'] = 'Show points link in user navigation';
$string['leaderboardsize'] = 'Leaderboard size';
$string['leaderboardsize_desc'] = 'Number of users to show in leaderboard';
$string['allownegative'] = 'Allow negative points';
$string['allownegative_desc'] = 'Allow users to have negative point balances';
$string['pointsname'] = 'Points display name';
$string['pointsname_desc'] = 'Custom name for points (e.g., "coins", "stars", "XP")';
$string['defaultpoints'] = 'Default Points Values';
$string['defaultpoints_desc'] = 'Default point values for common actions when creating new rules';
$string['defaultactivitycompletion'] = 'Activity completion';
$string['defaultcoursecompletion'] = 'Course completion';
$string['defaultforumpost'] = 'Forum post';
$string['defaultquizsubmission'] = 'Quiz submission';
$string['defaultassignmentsubmission'] = 'Assignment submission';

// Rules.
$string['rules'] = 'Rules';
$string['managerules'] = 'Manage Rules';
$string['createrule'] = 'Create Rule';
$string['editrule'] = 'Edit Rule';
$string['deleterule'] = 'Delete Rule';
$string['rulename'] = 'Rule Name';
$string['ruledescription'] = 'Description';
$string['ruleevent'] = 'Trigger Event';
$string['rulepoints'] = 'Points to Award';
$string['rulecourse'] = 'Course';
$string['ruleconditions'] = 'Conditions';
$string['ruleenabled'] = 'Enabled';
$string['rulemaxawards'] = 'Maximum Awards';
$string['rulemaxawards_help'] = 'Maximum number of times this rule can award points to a single user. Leave empty for unlimited.';
$string['globalrule'] = 'Global (all courses)';
$string['specificcourse'] = 'Specific course';
$string['rulesaved'] = 'Rule saved successfully';
$string['ruledeleted'] = 'Rule deleted successfully';
$string['confirmdeleterule'] = 'Are you sure you want to delete this rule?';
$string['norules'] = 'No rules defined yet';

// Events.
$string['event_activity_completed'] = 'Activity completed';
$string['event_course_completed'] = 'Course completed';
$string['event_user_graded'] = 'User graded';
$string['event_forum_discussion'] = 'Forum discussion created';
$string['event_forum_post'] = 'Forum post created';
$string['event_quiz_submitted'] = 'Quiz submitted';
$string['event_assignment_submitted'] = 'Assignment submitted';
$string['event_user_enrolled'] = 'User enrolled';
$string['event_user_login'] = 'User logged in';
$string['event_points_awarded'] = 'Points awarded';
$string['event_program_completed'] = 'Program completed';

// Activity and Program selectors.
$string['ruleactivity'] = 'Specific Activity';
$string['ruleactivity_help'] = 'Select a specific activity for this rule, or leave empty to apply to all activities in the course.';
$string['allactivities'] = 'All activities';
$string['ruleprogram'] = 'Program';
$string['ruleprogram_help'] = 'Select the program that must be completed to trigger this rule.';
$string['selectprogram'] = 'Select a program';

// Conditions.
$string['condition_min_grade'] = 'Minimum grade';
$string['condition_completion_state'] = 'Completion state';
$string['condition_activity_type'] = 'Activity type';

// View pages.
$string['pointsoverview'] = 'Points Overview';
$string['viewdetails'] = 'View Details';
$string['viewhistory'] = 'View History';
$string['pointshistory'] = 'Points History';
$string['leaderboard'] = 'Leaderboard';
$string['globalleaderboard'] = 'Global Leaderboard';
$string['courseleaderboard'] = 'Course Leaderboard';
$string['rank'] = 'Rank';
$string['user'] = 'User';
$string['date'] = 'Date';
$string['reason'] = 'Reason';
$string['nohistory'] = 'No points history yet';
$string['nopointsyet'] = 'No points earned yet';

// Award points.
$string['awardpoints'] = 'Award Points';
$string['awardto'] = 'Award to';
$string['pointstoaward'] = 'Points to award';
$string['awardreason'] = 'Reason';
$string['pointsawarded'] = 'Points awarded successfully';
$string['selectuser'] = 'Select user';
$string['manuallyawarded'] = 'Manually awarded';

// Errors.
$string['error_invaliduser'] = 'Invalid user';
$string['error_invalidcourse'] = 'Invalid course';
$string['error_invalidpoints'] = 'Invalid points value';
$string['error_nopermission'] = 'You do not have permission to perform this action';
$string['error_rulerequired'] = 'Rule name and event are required';

// Privacy.
$string['privacy:metadata:local_points_user'] = 'Stores user point totals';
$string['privacy:metadata:local_points_user:userid'] = 'User ID';
$string['privacy:metadata:local_points_user:points'] = 'Total points';
$string['privacy:metadata:local_points_history'] = 'History of points transactions';
$string['privacy:metadata:local_points_history:userid'] = 'User ID';
$string['privacy:metadata:local_points_history:points'] = 'Points awarded';
$string['privacy:metadata:local_points_history:reason'] = 'Reason for award';

// Misc.
$string['positive'] = 'Positive';
$string['negative'] = 'Negative';
$string['allcourses'] = 'All courses';
$string['selectcourse'] = 'Select course';
$string['norulesforevent'] = 'No rules for this event';
$string['actions'] = 'Actions';
$string['status'] = 'Status';
$string['active'] = 'Active';
$string['inactive'] = 'Inactive';
$string['backtorules'] = 'Back to rules';
$string['backtooverview'] = 'Back to overview';

// Task.
$string['task_process_points'] = 'Process points assignments';

// Report.
$string['pointsreport'] = 'Points Report';
$string['filters'] = 'Filters';
$string['filter'] = 'Filter';
$string['reset'] = 'Reset';
$string['allrules'] = 'All rules';
$string['allusers'] = 'All users';
$string['userid'] = 'User ID';
$string['awardedby'] = 'Awarded by';
$string['system'] = 'System';
$string['totalawards'] = 'Total Awards';
$string['totalpointsawarded'] = 'Total Points Awarded';
$string['uniqueusers'] = 'Unique Users';
$string['viewreport'] = 'View Report';
$string['exportreport'] = 'Export Report';

// Course admin.
$string['courserules'] = 'Course Rules';
$string['globalrules'] = 'Global Rules';
$string['ruleappliedtimes'] = 'Applied {$a} times';
$string['lastaward'] = 'Last award';
$string['rulestats'] = 'Rule Statistics';
$string['noawardsyet'] = 'No awards yet';

// Store.
$string['store'] = 'Rewards Store';
$string['rewards'] = 'Rewards';
$string['reward'] = 'Reward';
$string['managerewards'] = 'Manage Rewards';
$string['addreward'] = 'Add Reward';
$string['editreward'] = 'Edit Reward';
$string['rewardcreated'] = 'Reward created successfully';
$string['rewardupdated'] = 'Reward updated successfully';
$string['rewarddeleted'] = 'Reward deleted';
$string['rewarddisabled'] = 'Reward has been disabled (has redemptions)';
$string['norewards'] = 'No rewards defined';
$string['norewardsavailable'] = 'No rewards available at this time';

// Categories.
$string['category'] = 'Category';
$string['categories'] = 'Categories';
$string['managecategories'] = 'Manage Categories';
$string['addcategory'] = 'Add Category';
$string['editcategory'] = 'Edit Category';
$string['categorycreated'] = 'Category created successfully';
$string['categoryupdated'] = 'Category updated successfully';
$string['categorydeleted'] = 'Category deleted';
$string['categoryhasrewards'] = 'Cannot delete category with rewards';
$string['nocategories'] = 'No categories defined';
$string['nocategory'] = 'No category';
$string['allcategories'] = 'All categories';

// Redemptions.
$string['redemptions'] = 'Redemptions';
$string['redemption'] = 'Redemption';
$string['manageredemptions'] = 'Manage Redemptions';
$string['myredemptions'] = 'My Redemptions';
$string['redeem'] = 'Redeem';
$string['redeemnow'] = 'Redeem Now';
$string['redeemreward'] = 'Redeem Reward';
$string['redeemedreward'] = 'Redeemed: {$a}';
$string['confirmredemption'] = 'Confirm Redemption';
$string['confirmredemptionmessage'] = 'This action cannot be undone. Your points will be deducted immediately.';
$string['redemptionconfirmed'] = 'Redemption Confirmed';
$string['redemptionsuccess'] = 'Your redemption has been submitted!';
$string['redemptionpendingmessage'] = 'Your redemption is pending approval. You will be notified when it is processed.';
$string['redemptionerror'] = 'An error occurred during redemption';
$string['noredemptions'] = 'No redemptions found';

// Redemption statuses.
$string['status_pending'] = 'Pending';
$string['status_approved'] = 'Approved';
$string['status_rejected'] = 'Rejected';
$string['status_delivered'] = 'Delivered';
$string['statusupdated'] = 'Status updated successfully';

// Redemption actions.
$string['approve'] = 'Approve';
$string['reject'] = 'Reject';
$string['markdelivered'] = 'Mark as Delivered';
$string['redemptionrejectedrefund'] = 'Refund for rejected redemption';

// Store fields.
$string['cost'] = 'Cost (Points)';
$string['cost_help'] = 'Number of points required to redeem this reward';
$string['stock'] = 'Stock';
$string['stock_help'] = 'Available quantity. Leave empty for unlimited.';
$string['stockremaining'] = '{$a} remaining';
$string['unlimited'] = 'Unlimited';
$string['image'] = 'Image';
$string['image_help'] = 'Upload an image for the reward. Recommended size: 400x300 pixels.';
$string['availablefrom'] = 'Available from';
$string['availableuntil'] = 'Available until';
$string['pointsspent'] = 'Points Spent';
$string['remaining'] = 'Remaining after redemption';

// Store errors.
$string['insufficientpoints'] = 'You do not have enough points for this reward';
$string['needmorepoints'] = 'You need {$a} more points';
$string['rewardnotenabled'] = 'This reward is not available';
$string['rewardnotavailableyet'] = 'This reward is not available yet';
$string['rewardexpired'] = 'This reward is no longer available';
$string['rewardoutofstock'] = 'This reward is out of stock';
$string['notavailable'] = 'Not Available';
$string['invalidcost'] = 'Invalid cost value';
$string['invalidquantity'] = 'Invalid quantity value';
$string['confirmdelete'] = 'Are you sure you want to delete this item?';

// Store navigation.
$string['backtostore'] = 'Back to Store';
$string['viewdetails'] = 'View Details';

// Store sorting and labels.
$string['sortby'] = 'Sort by';
$string['popular'] = 'Popular';
$string['pricelowtohigh'] = 'Price: Low to High';
$string['pricehightolow'] = 'Price: High to Low';
$string['newest'] = 'Newest';
$string['checkbacklater'] = 'Check back later for new rewards';
$string['only'] = 'Only';
$string['left'] = 'left';
$string['available'] = 'Available';
$string['need'] = 'Need';
