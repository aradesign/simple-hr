@include('admin.applications._form-document', [
    'application' => $application,
    'layout' => $layout,
    'profilePhotoDataUri' => $profilePhotoDataUri ?? null,
    'profilePhotoUrl' => $profilePhotoUrl ?? null,
    'initials' => $initials,
    'applicantName' => $applicantName,
    'forPdf' => false,
    'showToolbar' => true,
])
