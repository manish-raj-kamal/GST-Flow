<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="module-kicker">Account</p>
            <div class="page-title-row">
                <h1 class="module-title">Profile Settings</h1>
                <x-info-tip placement="bottom" text="Update your name, email, password, and account status from this page. Test accounts may have some security restrictions." />
            </div>
            <p class="module-subtitle hidden md:block">Manage your account identity and security preferences.</p>
        </div>
    </x-slot>

    <div class="p-4 sm:p-6 lg:p-8">
        <div class="mx-auto max-w-2xl space-y-6">
            <div class="card-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="card-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="card-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
