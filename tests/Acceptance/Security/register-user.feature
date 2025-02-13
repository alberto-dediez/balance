Feature:
  I want to make sure that the API allow to register an user

  Scenario: Command security:user:register is executed without errors
    Given I am the system
    When command "security:user:register" is executed with argument "username:an@email.com password:123"
    Then Is Executed Successfully
    Then I want to see "userId" in command output
    Then I want to see "username" in command output
    Then I want to see "an@email.com" in command output