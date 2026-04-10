Feature:
  I want to make sure that the software allow to register a training session

  Scenario: As an admin, I can't access to register new training sessions
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/training-sessions/register"
    Then the response status should be 403

  Scenario: As a normal user, I can't access to register new training sessions
    Given account "MyCompany" with id "mycompany" exists
    Given user "user1@dediez.software" with role "ROLE_USER" of account "mycompany" exists
    When I am logged as "user1@dediez.software"
    When I go to "/es/training-sessions/register"
    Then the response status should be 403

  Scenario: As an owner, I can access the register form
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/training-sessions/register"
    Then the response status should be 200
    Then I see a form

  Scenario: As an owner, I can register a new training session
    Given account "MyCompany" with id "mycompany" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/training-sessions/register"
    When I submit a form with "sessionDate:2024-03-15|title:Reunión sobre protocolo de donación|topic:Protocolo de donación" and button "Registrar"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Sesión formativa creada correctamente: Reunión sobre protocolo de donación"
