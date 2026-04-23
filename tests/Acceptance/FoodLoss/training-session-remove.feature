Feature:
    I want to make sure that the system is allow to remove a training session

    Scenario: As an owner, I can remove my training sessions
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given training session "Session 1" with id "333" of account "mycompany" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/training-sessions/remove/333"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Se ha eliminado la sesión formativa Session 1"

    Scenario: As a normal user I cannot remove training sessions
        Given account "MyCompany" with id "mycompany" exists
        Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
        Given training session "Session 1" with id "333" of account "mycompany" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/training-sessions/remove/333"
        Then the response status should be 403
