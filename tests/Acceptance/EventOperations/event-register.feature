Feature:
    I want to make sure that the software allow to register a event

    Scenario: As an owner, I can access to register new events
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/register"
        Then the response status should be 200
        Then I see a form

    Scenario: As normal user I can't register new events
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/events/register"
        Then the response status should be 403
