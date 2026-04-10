Feature:
  I want to make sure that the system allows to edit a training session

  Scenario: As an owner, I can access to edit my training sessions
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given training session "Session 1" with id "333" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/training-sessions/edit/333"
    When I submit a form with "sessionDate:2024-04-01|title:Updated title|topic:Updated topic" and button "Guardar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Sesión formativa actualizada con éxito"

  Scenario: As an owner from another account, I cannot edit training sessions
    Given account "MyCompany" with id "mycompany" exists
    Given account "OtherCompany" with id "othercompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    Given training session "Session 1" with id "333" of account "othercompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/training-sessions/edit/333"
    Then the response status should be 403

  Scenario: As a normal user, I cannot edit training sessions
    Given account "MyCompany" with id "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
    Given training session "Session 1" with id "333" of account "mycompany" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/training-sessions/edit/333"
    Then the response status should be 403
