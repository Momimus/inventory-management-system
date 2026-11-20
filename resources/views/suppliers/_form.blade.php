<x-form.input name="name" label="Company name" :value="$supplier->name" required maxlength="150" />

<div class="row">
    <div class="col-md-6">
        <x-form.input name="contact_person" label="Contact person" :value="$supplier->contact_person" maxlength="150" />
    </div>
    <div class="col-md-6">
        <x-form.input name="phone" type="tel" label="Phone" :value="$supplier->phone" maxlength="30" />
    </div>
</div>

<x-form.input name="email" type="email" label="Email address" :value="$supplier->email" maxlength="255" />
<x-form.textarea name="address" label="Address" :value="$supplier->address" maxlength="500" />
<x-form.switch name="is_active" label="Active supplier" :checked="$supplier->is_active"
               help="Inactive suppliers stay in the records but cannot be chosen for new products or deliveries." />
