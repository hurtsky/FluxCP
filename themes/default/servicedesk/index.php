<?php
if (!defined('FLUX_ROOT')) {
    exit;
}
$this->loginRequired();
?>
<h2><?php echo htmlspecialchars(Flux::message('SDHeader')) ?></h2>
<p><?php echo htmlspecialchars(Flux::message('SDWelcomeText')) ?>, <?php echo $session->account->userid ?>. </p>
<p>
		<a href="<?php echo $this->url('servicedesk', 'create') ?>"><?php echo Flux::message('SDLinkOpenNew') ?></a>
</p>
<h3><?php echo Flux::message('SDH3ActiveTickets') ?></h3>
<?php if ($rowoutput): ?>
	<table class="horizontal-table" width="100%"> 
		<tbody>
		<tr>
			<th><?php  echo htmlspecialchars(Flux::message('SDHeaderID')) ?></th>
			<th><?php  echo htmlspecialchars(Flux::message('SDHeaderSubject')) ?></th>    
			<th><?php  echo htmlspecialchars(Flux::message('SDHeaderCategory')) ?></th>    
			<th><?php  echo htmlspecialchars(Flux::message('SDHeaderStatus')) ?></th> 
			<th><?php  echo htmlspecialchars(Flux::message('SDHeaderLastAuthor')) ?></th>
			<th><?php  echo htmlspecialchars(Flux::message('SDHeaderTeam')) ?></th>
			<th><?php  echo htmlspecialchars(Flux::message('SDHeaderTimestamp')) ?></th>   
		</tr>
		<?php echo $rowoutput ?>
		</tbody>
	</table>
<?php else: ?>
	<p>
		<?php echo Flux::message('SDNoOpenTickets') ?><br /><br />
	</p>
<?php endif ?><br /><Br />
<h3><?php echo Flux::message('SDH3InActiveTickets') ?></h3>
<?php if ($oldrowoutput): ?>
	<table class="horizontal-table" width="100%"> 
		<tbody>
		<tr>
			<th><?php  echo htmlspecialchars(Flux::message('SDHeaderID')) ?></th>
			<th><?php  echo htmlspecialchars(Flux::message('SDHeaderSubject')) ?></th>    
			<th><?php  echo htmlspecialchars(Flux::message('SDHeaderCategory')) ?></th>    
			<th><?php  echo htmlspecialchars(Flux::message('SDHeaderStatus')) ?></th> 
			<th><?php  echo htmlspecialchars(Flux::message('SDHeaderLastAuthor')) ?></th>
			<th><?php  echo htmlspecialchars(Flux::message('SDHeaderTeam')) ?></th>
			<th><?php  echo htmlspecialchars(Flux::message('SDHeaderTimestamp')) ?></th>   
		</tr>
		<?php echo $oldrowoutput ?>
		</tbody>
	</table>
<?php else: ?>
	<p>
		<?php echo Flux::message('SDNoInactiveTickets') ?><br /><br />
	</p>
<?php endif ?><br /><Br />
