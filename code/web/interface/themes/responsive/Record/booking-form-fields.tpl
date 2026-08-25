{strip}
{include file="Record/date-range-picker.tpl" rangeId="booking-calendar" startName="startDate" endName="endDate" startValue=$startDate|default:'' endValue=$endDate|default:'' months=1 statusText="Loading availability…"}
{include file='Record/pickup-location-select.tpl'}

<script>AspenDiscovery.Record.initBookingForm();</script>
{/strip}
