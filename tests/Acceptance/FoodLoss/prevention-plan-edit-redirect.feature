Feature:
    I want to make sure that the base prevention plan edit URL redirects to the active step based on the plan status

    Scenario: An InProgress plan redirects to the general step
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given prevention plan "Plan 1" with id "333" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/prevention-plans/edit/333"
        Then I am redirected to "/es/prevention-plans/edit/333/general"

    Scenario: A Validated plan redirects to the validate step
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given prevention plan "Plan 1" with id "333" of account "mycompany" exists
        Given prevention plan "333" is mark as validated
        When I am logged as "owner@dediez.software"
        When I go to "/es/prevention-plans/edit/333"
        Then I am redirected to "/es/prevention-plans/edit/333/validate"

    Scenario: A Configured plan redirects to the actions step
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given prevention plan "Plan 1" with id "333" of account "mycompany" in configured state exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/prevention-plans/edit/333"
        Then I am redirected to "/es/prevention-plans/edit/333/actions"

    Scenario: A Generated plan redirects to the draft step
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given prevention plan "Plan 1" with id "333" of account "mycompany" with generated sections exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/prevention-plans/edit/333"
        Then I am redirected to "/es/prevention-plans/edit/333/draft"
