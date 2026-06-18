@include('admin.applications._form-document', [
    'application' => $application,
    'layout' => $layout,
    'profilePhotoDataUri' => $profilePhotoDataUri,
    'profilePhotoUrl' => null,
    'initials' => $initials,
    'applicantName' => $applicantName,
    'forPdf' => true,
    'showToolbar' => false,
])
