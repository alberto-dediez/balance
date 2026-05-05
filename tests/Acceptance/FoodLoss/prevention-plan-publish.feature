Feature:
    I want to make sure that the system allows publishing a prevention plan after draft approval

    Scenario: As an admin, I can't access the publish step
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given prevention plan "Plan 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/prevention-plans/edit/333/publish"
        Then the response status should be 403

    Scenario: As an owner, I can access the publish step when the draft is approved
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given prevention plan "Plan 1" with id "333" of account "mycompany" with draft approved status exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/prevention-plans/edit/333/publish"
        Then the response status should be 200
        Then I see a form field "signed"

    Scenario: As an owner, I am redirected to edit when plan is not yet draft approved
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given prevention plan "Plan 1" with id "333" of account "mycompany" with generated sections exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/prevention-plans/edit/333/publish"
        Then I am redirected to "/es/prevention-plans/edit/333"

    Scenario: As an owner, I cannot access the publish step of a plan from another account
        Given account "MyCompany" with id "mycompany" exists
        Given account "OtherCompany" with id "othercompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given prevention plan "Plan 1" with id "333" of account "othercompany" with draft approved status exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/prevention-plans/edit/333/publish"
        Then the response status should be 403

    Scenario: As a normal user I can't access the publish step
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given prevention plan "Plan 1" with id "333" of account "mycompany" with draft approved status exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/prevention-plans/edit/333/publish"
        Then the response status should be 403

    Scenario: As an owner, submitting the signed checkbox completes the plan and redirects to list
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given prevention plan "Plan 1" with id "333" of account "mycompany" with draft approved status exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/prevention-plans/edit/333/publish"
        When I submit a form with "signed:1" and button "Publicar"
        Then I am redirected to "/es/prevention-plans"
