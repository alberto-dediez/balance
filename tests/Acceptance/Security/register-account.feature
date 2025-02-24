Feature:
  I want to make sure that the API allow to register an account

  Scenario: Command security:account:register is executed without errors
    Given I am the system
    When command "security:account:register" is executed with argument "name:ACompany active:true"
    Then Is Executed Successfully
    Then I want to see "accountId" in command output
    Then I want to see "name" in command output
    Then I want to see "ACompany" in command output
    Then I want to see "Activa" in command output

  Scenario: Command security:account:register is executed without errors even with a inactive account
    Given I am the system
    When command "security:account:register" is executed with argument "name:ACompany active:false"
    Then Is Executed Successfully
    Then I want to see "Inactiva" in command output