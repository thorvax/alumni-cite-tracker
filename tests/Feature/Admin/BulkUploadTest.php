<?php

use App\Jobs\SendSurveyInvitation;
use App\Mail\SurveyInvitationMail;
use App\Models\Graduate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

function csvUpload(string $body): UploadedFile
{
    return UploadedFile::fake()->createWithContent('students.csv', $body);
}

function invitedGraduate(array $attributes = []): Graduate
{
    return Graduate::factory()->create($attributes + [
        'student_id' => '20240001',
        'last_name' => 'Cruz',
        'full_name' => 'Cruz',
        'email' => 'cruz@example.com',
        'invite_token' => str_repeat('a', 48),
        'invite_status' => 'sent',
        'employment_status' => null,
    ]);
}

it('keeps the bulk upload pages behind the login', function () {
    $this->get('/graduates/import')->assertRedirect('/login');
    $this->get('/graduates/import/template')->assertRedirect('/login');
});

it('downloads the Excel template', function () {
    $this->actingAs($this->admin)
        ->get('/graduates/import/template')
        ->assertOk()
        ->assertDownload('cite-students-template.xlsx');
});

it('reports the exact cells that are wrong and saves nothing', function () {
    $csv = "Student ID Number,Last Name,Email\n20240001,Cruz,a@example.com\n20240001,Santos,\n2024,,bad-email\n";

    $this->actingAs($this->admin)
        ->post('/graduates/import/preview', ['file' => csvUpload($csv)])
        ->assertRedirect('/graduates/import');

    $this->actingAs($this->admin)
        ->get('/graduates/import')
        ->assertInertia(fn (Assert $page) => $page
            ->where('preview.errors.0.cell', 'A3')
            ->where('preview.errors.0.type', 'duplicate')
            ->has('preview.errors', 4));

    $this->actingAs($this->admin)->post('/graduates/import/confirm')->assertSessionHasErrors('file');

    expect(Graduate::count())->toBe(0);
});

it('lists IDs that are already uploaded and skips them on confirm', function () {
    Queue::fake();
    invitedGraduate(['student_id' => '20240001']);

    $csv = "Student ID Number,Last Name,Email\n20240001,Cruz,cruz@example.com\n20240002,Santos,santos@example.com\n20240003,Reyes,\n";

    $this->actingAs($this->admin)->post('/graduates/import/preview', ['file' => csvUpload($csv)]);

    $this->actingAs($this->admin)
        ->get('/graduates/import')
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.already', 1)
            ->where('preview.already.0.student_id', '20240001')
            ->where('preview.new_count', 2)
            ->where('preview.without_email', 1));

    $this->actingAs($this->admin)->post('/graduates/import/confirm')->assertRedirect('/graduates/invitations');

    expect(Graduate::where('student_id', '20240002')->exists())->toBeTrue()
        ->and(Graduate::where('student_id', '20240003')->first()->invitationStatus())->toBe('no_email')
        ->and(Graduate::whereNotNull('student_id')->count())->toBe(3);

    // Only the one new person with an email is queued.
    Queue::assertPushed(SendSurveyInvitation::class, 1);
});

it('imports the optional Course and Year Graduated columns', function () {
    Queue::fake();
    $csv = "Student ID Number,Last Name,Email,Course,Year Graduated\n"
        ."20240001,Cruz,,bsit,2024\n"
        ."20240002,Santos,,Bachelor of Library in Information Science,2023\n"
        ."20240003,Reyes,,,\n";

    $this->actingAs($this->admin)->post('/graduates/import/preview', ['file' => csvUpload($csv)]);
    $this->actingAs($this->admin)->post('/graduates/import/confirm')->assertRedirect('/graduates/invitations');

    $cruz = Graduate::where('student_id', '20240001')->first();
    $santos = Graduate::where('student_id', '20240002')->first();
    $reyes = Graduate::where('student_id', '20240003')->first();

    expect($cruz->course)->toBe('Bachelor of Science in Information and Technology')
        ->and($cruz->year_graduated)->toBe(2024)
        ->and($santos->courseCode())->toBe('BLIS')
        ->and($reyes->course)->toBeNull()
        ->and($reyes->year_graduated)->toBeNull();
});

it('reports a wrong course or year by cell', function () {
    $nextYear = (int) date('Y') + 1;
    $csv = "Student ID Number,Last Name,Course,Year Graduated\n"
        ."20240001,Cruz,Nursing,2024\n"
        ."20240002,Santos,ACT,{$nextYear}\n"
        ."20240003,Reyes,ACT,'24\n";

    $this->actingAs($this->admin)->post('/graduates/import/preview', ['file' => csvUpload($csv)]);

    $this->actingAs($this->admin)
        ->get('/graduates/import')
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview.errors', 3)
            ->where('preview.errors.0.cell', 'C2')
            ->where('preview.errors.0.type', 'invalid_course')
            ->where('preview.errors.1.cell', 'D3')
            ->where('preview.errors.1.type', 'invalid_year')
            ->where('preview.errors.2.cell', 'D4'));
});

it('fills survey step B with the uploaded course and year', function () {
    loginGraduate(['course' => 'Associate in Computer Technology', 'year_graduated' => 2024]);

    $this->get('/survey/educational-background')
        ->assertInertia(fn (Assert $page) => $page
            ->where('draft.course', 'Associate in Computer Technology')
            ->where('draft.year_graduated', '2024'));
});

it('cancels a preview without saving', function () {
    $csv = "Student ID Number,Last Name,Email\n20240002,Santos,\n";

    $this->actingAs($this->admin)->post('/graduates/import/preview', ['file' => csvUpload($csv)]);
    $this->actingAs($this->admin)->delete('/graduates/import')->assertRedirect('/graduates/import');
    $this->actingAs($this->admin)->post('/graduates/import/confirm')->assertSessionHasErrors('file');

    expect(Graduate::count())->toBe(0);
});

it('sends the invitation email and marks it sent', function () {
    Mail::fake();
    $graduate = invitedGraduate(['invite_status' => 'queued']);

    (new SendSurveyInvitation($graduate->id))->handle();

    Mail::assertSent(SurveyInvitationMail::class, fn ($mail) => $mail->hasTo('cruz@example.com'));

    expect($graduate->fresh()->invite_status)->toBe('sent')
        ->and($graduate->fresh()->invite_sent_at)->not->toBeNull();
});

it('marks the email failed when sending throws', function () {
    Mail::shouldReceive('to->send')->andThrow(new RuntimeException('Mailbox unavailable'));
    $graduate = invitedGraduate(['invite_status' => 'queued']);

    (new SendSurveyInvitation($graduate->id))->handle();

    expect($graduate->fresh()->invite_status)->toBe('failed')
        ->and($graduate->fresh()->invite_error)->toContain('Mailbox unavailable');
});

it('does not email someone who already answered', function () {
    Mail::fake();
    $graduate = invitedGraduate(['survey_completed_at' => now()]);

    (new SendSurveyInvitation($graduate->id))->handle();

    Mail::assertNothingSent();
});

it('resends one email', function () {
    Queue::fake();
    $graduate = invitedGraduate(['invite_status' => 'failed']);

    $this->actingAs($this->admin)->post("/graduates/import/{$graduate->id}/resend")->assertRedirect();

    Queue::assertPushed(SendSurveyInvitation::class);
    expect($graduate->fresh()->invite_status)->toBe('queued');
});

it('saves an email for someone without one and queues it', function () {
    Queue::fake();
    $graduate = invitedGraduate(['email' => null, 'invite_status' => 'pending']);

    $this->actingAs($this->admin)
        ->patch("/graduates/import/{$graduate->id}/email", ['email' => 'New@Example.com'])
        ->assertRedirect();

    expect($graduate->fresh()->email)->toBe('new@example.com');
    Queue::assertPushed(SendSurveyInvitation::class);
});

it('rejects a bad email for someone without one', function () {
    $graduate = invitedGraduate(['email' => null]);

    $this->actingAs($this->admin)
        ->patch("/graduates/import/{$graduate->id}/email", ['email' => 'nope'])
        ->assertSessionHasErrors('email');
});

it('sends all typed emails at once and ignores blank rows', function () {
    Queue::fake();
    $one = invitedGraduate(['student_id' => '20240001', 'invite_token' => str_repeat('a', 48), 'email' => null]);
    $two = invitedGraduate(['student_id' => '20240002', 'invite_token' => str_repeat('b', 48), 'email' => null]);

    $this->actingAs($this->admin)
        ->post('/graduates/import/emails', ['emails' => [$one->id => 'one@example.com', $two->id => '']])
        ->assertRedirect();

    expect($one->fresh()->email)->toBe('one@example.com')
        ->and($two->fresh()->email)->toBeNull();
    Queue::assertPushed(SendSurveyInvitation::class, 1);
});

it('sends reminders manually only to people who have not answered', function () {
    Queue::fake();
    invitedGraduate(['student_id' => '20240001', 'invite_token' => str_repeat('a', 48)]);
    invitedGraduate(['student_id' => '20240002', 'invite_token' => str_repeat('b', 48), 'survey_completed_at' => now()]);

    $this->actingAs($this->admin)->post('/graduates/import/remind')->assertRedirect();

    Queue::assertPushed(SendSurveyInvitation::class, fn ($job) => $job->reminder === true);
    Queue::assertPushed(SendSurveyInvitation::class, 1);
});

it('saves the reminder schedule and the command respects it', function () {
    Queue::fake();
    $this->actingAs($this->admin)
        ->patch('/graduates/import/schedule', ['enabled' => true, 'interval_days' => 3, 'max' => 2, 'hour' => 8])
        ->assertRedirect();

    invitedGraduate(['invite_sent_at' => now()->subDays(4)]);

    $this->artisan('cite:send-reminders', ['--force' => true])->assertSuccessful();

    Queue::assertPushed(SendSurveyInvitation::class, 1);
});

it('does not remind people sooner than the interval', function () {
    Queue::fake();
    invitedGraduate(['invite_sent_at' => now()->subDay()]);

    $this->artisan('cite:send-reminders', ['--force' => true])->assertSuccessful();

    Queue::assertNothingPushed();
});

it('prefills the survey for a logged-in graduate and updates the same record on submit', function () {
    $graduate = invitedGraduate();

    // The personal link now starts account activation (see tests/Feature/Alumni).
    $this->get('/survey/start/'.$graduate->invite_token)->assertRedirect('/alumni/activate');

    $this->actingAs($graduate, 'graduate')
        ->withSession(['survey.consented_at' => now()->toIso8601String()]) // agreed on the letter page
        ->get('/survey/general-information')
        ->assertInertia(fn (Assert $page) => $page
            ->where('draft.last_name', 'Cruz')
            ->where('draft.email', 'cruz@example.com')
            ->where('invite.student_id', '20240001'));

    $answers = surveyAnswers([
        'employed' => 'no',
        'not_employed_reasons' => ['advance_study'],
    ]);

    $this->actingAs($graduate, 'graduate')
        ->withSession(['survey' => $answers])
        ->post('/survey/referral-submit', ['classmates' => null, 'certify' => true])
        ->assertRedirect('/alumni/my-survey');

    expect(Graduate::count())->toBe(1)
        ->and($graduate->fresh()->survey_completed_at)->not->toBeNull()
        ->and($graduate->fresh()->student_id)->toBe('20240001')
        ->and($graduate->fresh()->employment_status)->toBe('Further Studies');
});

it('requires an email address in the personal details', function () {
    loginGraduate();

    $this->post('/survey/general-information', ['email' => 'not-an-email'])
        ->assertSessionHasErrors('email');
});

it('ignores an unknown or used personal link', function () {
    $this->get('/survey/start/'.str_repeat('z', 48))->assertRedirect('/');
});
