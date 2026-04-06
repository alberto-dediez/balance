Feature:
  I want to make sure that the system is allow to remove a delivery log

  Scenario: As an owner, I can access to remove delivery logs
    Given account "MyAccount" with id "my-account" exists
    Given user "owner@dediez.software" with role "ROLE_OWNER" of account "my-account" exists
    Given delivery log "Item 1" with id "log-1" of account "my-account" exists
    When I am logged as "owner@dediez.software"
    When I go to "/es/delivery-logs/remove/log-1"
    When I follow redirect
    Then the response status should be 200
    Then I see a text "Se ha eliminado el registro de entrega de Item 1"

  Scenario: As an admin I can't remove delivery logs
    Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
    Given account "MyAccount" with id "my-account" exists
    Given delivery log "Item 1" with id "log-1" of account "my-account" exists
    When I am logged as "admin@dediez.software"
    When I go to "/es/delivery-logs/remove/log-1"
    Then the response status should be 403
