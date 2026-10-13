{strip}
{include file='Record/pickup-location-select.tpl'}
{include file="Record/date-range-picker.tpl" rangeId="booking-calendar" startName="startDate" endName="endDate" startValue=$startDate|default:'' endValue=$endDate|default:'' months=1 statusText="Loading availability…"}

<script>AspenDiscovery.Record.initBookingForm({if !empty($availability)}{json_encode($availability)}{/if});</script>
{/strip}
