{{-- Contact form shared by the Orion family; submitted by partials/scripts. --}}
<form id="contact-form" class="contact-form" novalidate>
    <div class="two">
        <label><span class="label">Name</span><input name="name" autocomplete="name" required></label>
        <label><span class="label">Email</span><input name="email" type="email" autocomplete="email" required></label>
    </div>
    <label><span class="label">Subject</span><input name="subject" required></label>
    <label><span class="label">Message</span><textarea name="body" rows="6" required></textarea></label>
    <div><button class="btn primary" type="submit">Send message</button></div>
    <p class="form-note" id="form-note" role="status"></p>
</form>
