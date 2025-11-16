<!-- This page will reuse the chat component from technical dashboard -->
<div class="h-full">
    <?php include 'technical/chat.php'; ?>
</div>

<script>
// Customize chat for trainer role
if (typeof ChatSystem !== 'undefined') {
    ChatSystem.userRole = 'trainer';
}
</script>
