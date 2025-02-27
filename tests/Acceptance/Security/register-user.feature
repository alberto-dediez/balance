Feature:
  I want to make sure that the system is allow to register an user

  Scenario: Command security:user:register is executed without errors
    Given I am the system
    When command "security:user:register" is executed with argument "username:an@email.com password:123"
    Then Is Executed Successfully
    Then I want to see "userId" in command output
    Then I want to see "username" in command output
    Then I want to see "an@email.com" in command output

  Scenario: I can register a user inside an account
    Given I am the system
    Given account "Minotour" with id "6666-6666-6666" exists
    When command "security:user:register" is executed with argument "username:an@email.com password:123 accountId:6666-6666-6666"
    Then Is Executed Successfully
    Then I want to see "userId" in command output
    Then I want to see "username" in command output
    Then I want to see "an@email.com" in command output
    Then I want to see "6666-6666-6666" in command output